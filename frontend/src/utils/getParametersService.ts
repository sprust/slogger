interface Data {
    [key: string]: string;
}

export class GetParametersService {
    private static data: Data = {}

    public static set<T>(key: string, parameters: T) {
        const serializedParameters = JSON.stringify(parameters);

        GetParametersService.data[key] = serializedParameters

        const urlParams = new URLSearchParams()

        urlParams.set('p', serializedParameters)

        const url = window.location.protocol + '//'
            + window.location.host
            + window.location.pathname
            + '?' + urlParams.toString()

        window.history.pushState({path: url}, '', url);
    }

    public static restoreToUrl<T>(key: string, defaultValue: T): T {
        const serializedParameters: string | null = GetParametersService.data[key] ?? null

        if (!serializedParameters) {
            return defaultValue
        }

        return JSON.parse(serializedParameters) as T
    }

    public static restoreFromUrl<T>(key: string, defaultValue: T): T {
        const urlParams = new URLSearchParams(window.location.search);

        const serializedParameters: string | null = urlParams.get('p')

        if (!serializedParameters) {
            return defaultValue
        }

        const value: string | null = GetParametersService.data[key] ?? null

        if (!value) {
            return defaultValue
        }

        return JSON.parse(serializedParameters) as T
    }
}
