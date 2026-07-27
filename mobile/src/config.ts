import Constants from 'expo-constants';

/**
 * API base URL. Defaults to the local Laravel dev server; override per build via
 * app.json → expo.extra.apiUrl (or an EAS environment variable).
 */
export const API_URL: string =
    (Constants.expoConfig?.extra?.apiUrl as string | undefined) ?? 'http://localhost:8000/api/v1';

/** Deep-link scheme configured in app.json. Used for notification/email links. */
export const APP_SCHEME = 'chittyfund';
