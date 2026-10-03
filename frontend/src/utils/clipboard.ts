import alerts from "./alerts.ts";

export async function copyToClipboard(value: string) {
    if (window.isSecureContext && navigator.clipboard) {
        await navigator.clipboard.writeText(value)
    } else {
        const textArea = document.createElement("textarea");

        textArea.value = value;

        document.body.appendChild(textArea);

        textArea.focus();

        textArea.select();

        try {
            document.execCommand('copy');
        } catch (err) {
            alerts.error('Unable to copy to clipboard')
        }

        document.body.removeChild(textArea);
    }
}
