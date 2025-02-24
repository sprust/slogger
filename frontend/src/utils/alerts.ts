import {ElMessage} from "element-plus";

export default {
  error(message: string): void {
    ElMessage.error({
      dangerouslyUseHTMLString: true,
      message: message,
      showClose: true,
      duration: 5000
    })
  },
  success(message: string): void {
    ElMessage.success({
      dangerouslyUseHTMLString: true,
      message: message,
      showClose: true,
      duration: 5000
    })
  }
}
