import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';

interface StatCardProps {
    title: string;
    value: string | number;
    icon?: LucideIcon;
    hint?: string;
    accent?: 'default' | 'amber' | 'red' | 'emerald' | 'blue';
}

const accentClasses: Record<NonNullable<StatCardProps['accent']>, string> = {
    default: 'text-foreground',
    amber: 'text-amber-600 dark:text-amber-500',
    red: 'text-red-600 dark:text-red-500',
    emerald: 'text-emerald-600 dark:text-emerald-500',
    blue: 'text-blue-600 dark:text-blue-500',
};

export function StatCard({ title, value, icon: Icon, hint, accent = 'default' }: StatCardProps) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
                {Icon && <Icon className={cn('h-4 w-4', accentClasses[accent])} />}
            </CardHeader>
            <CardContent>
                <div className={cn('text-2xl font-bold tracking-tight', accentClasses[accent])}>{value}</div>
                {hint && <p className="mt-1 text-xs text-muted-foreground">{hint}</p>}
            </CardContent>
        </Card>
    );
}
