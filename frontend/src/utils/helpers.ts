import {ElMessage} from "element-plus";

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

export async function copyToClipboard(value: string) {
  await navigator.clipboard.writeText(value)
}

export function handleApiError(error: any) {
  let message = '' + (error?.error?.message ?? error ?? 'Unknown error')

  const errorsList = Object.values(error?.error?.errors ?? []).flat()

  if (errorsList.length) {
    message += '<br><ul>'

    errorsList.map((errorItemText) => {
      message += `<li>${errorItemText}</li>`
    })

    message += '</ul>'
  }

  ElMessage.error({
    dangerouslyUseHTMLString: true,
    message: message,
    showClose: true,
    duration: 5000
  })
}

const zeroPad = (num: number, places: number): string => String(num).padStart(places, '0')

export function convertDateStringToLocal(dateString: string): string {
  const date = new Date(dateString)

  const newDate = new Date(date.getTime() + date.getTimezoneOffset() * 60 * 1000);

  const offset = date.getTimezoneOffset() / 60;

  newDate.setHours(date.getHours() - offset);

  const year = newDate.getFullYear()
  const month = zeroPad(newDate.getMonth() + 1, 2)
  const day = zeroPad(newDate.getDate(), 2)
  const hours = zeroPad(newDate.getHours(), 2)
  const minutes = zeroPad(newDate.getMinutes(), 2)
  const seconds = zeroPad(newDate.getSeconds(), 2)
  const microseconds = newDate.getMilliseconds()

  return [year, month, day].join('-') + ' ' + [hours, minutes, seconds.toLocaleString()].join(':') + '.' + microseconds;
}
