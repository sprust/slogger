import {Api} from "../api-schema/admin-api-schema.ts";

export class ApiTokenStorage {
    constructor() {
        throw new Error("Forbidden!")
    }

    public static getToken(): string | null {
        return localStorage.getItem("bearerToken")
    }

    public static setToken(token: string): void {
        localStorage.setItem("bearerToken", token)
    }

    public static forgetToken(): void {
        localStorage.removeItem("bearerToken")
    }
}

export class ApiContainer {
    constructor() {
        throw new Error("Forbidden!")
    }

    public static get() {
        return new Api({
            baseUrl: import.meta.env.VITE_BACKEND_URL,
            baseApiParams: {
                headers: {
                    "Accept": "application/json",
                    "Content-type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "Authorization": `Bearer ${ApiTokenStorage.getToken()}`,
                }
            }
        }).adminApi
    }
}
