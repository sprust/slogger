export class TypesHelper {
    public static isValueInt(value: any): boolean {
        return Number.isInteger(value)
    }

    public static isValueFloat(value: any): boolean {
        return !Number.isInteger(value) && Number.isFinite(value)
    }

    public static isValueBool(value: any): boolean {
        return typeof value == "boolean"
    }
}
