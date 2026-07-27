<?php

namespace App\Http\Controllers\Portal;

use App\Enums\KycDocumentType;
use App\Enums\KycStatus;
use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Customer-side KYC document upload. Files are stored on the PRIVATE 'local'
 * disk with randomized names; only metadata is public. Uploading moves the
 * customer's KYC status to "submitted" for staff review.
 */
class KycController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => ['required', new Enum(KycDocumentType::class)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'], // 4 MB
        ]);

        $profile = $user->customerProfile()->firstOrCreate(['company_id' => $user->company_id]);

        $file = $request->file('file');
        $path = $file->storeAs(
            "kyc/{$user->ulid}",
            Str::ulid().'.'.$file->getClientOriginalExtension(),
            'local', // private disk
        );

        KycDocument::create([
            'company_id' => $user->company_id,
            'customer_id' => $user->id,
            'customer_profile_id' => $profile->id,
            'type' => $validated['type'],
            'disk' => 'local',
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        // Move KYC to "submitted" if it was pending, so it enters the review queue.
        if ($profile->kyc_status === KycStatus::Pending) {
            $profile->update(['kyc_status' => KycStatus::Submitted->value]);
        }

        return back()->with('success', 'Document uploaded for review.');
    }

    public function download(Request $request, KycDocument $document): StreamedResponse
    {
        // Customers may only download their own documents.
        abort_unless($document->customer_id === $request->user()->id, 403);

        $disk = Storage::disk($document->disk);
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->download($document->file_path, $document->original_name);
    }

    public function destroy(Request $request, KycDocument $document): RedirectResponse
    {
        abort_unless($document->customer_id === $request->user()->id, 403);

        // Only allow removing documents that haven't been verified yet.
        abort_if($document->status->value === 'verified', 403, 'Verified documents cannot be removed.');

        Storage::disk($document->disk)->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }
}
