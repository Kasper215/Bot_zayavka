import axios, {AxiosResponse, AxiosRequestConfig} from "axios";
import {useAlertStore} from "./useAlertStore";

export async function makeAxiosFactory(
    link: string,
    method: string = "GET",
    data: any = null,
    config: AxiosRequestConfig | null = null
):  // @ts-ignore
    Promise<AxiosResponse<any>> {
    if (!navigator.onLine) {
        const alertStore = useAlertStore();
        alertStore.show("Вы не в сети!");
        // @ts-ignore
        return Promise.reject("Вы не в сети!");
    }

    // Не требуем Telegram WebApp жестко. Пытаемся взять данные, если они есть.
    let tgData = null;
    try {
        if (window && (window as any).Telegram && (window as any).Telegram.WebApp) {
            tgData = (window as any).Telegram.WebApp.initData || null;
        }
    } catch (e) {
        // Игнорируем ошибку, если нет объекта window или Telegram
    }
    
    if (tgData) {
        axios.defaults.headers.common["X-TG-DATA"] = btoa(tgData);
    } else {
        delete axios.defaults.headers.common["X-TG-DATA"];
    }

    const alertStore = useAlertStore();

    try {
        let response: AxiosResponse<any>;
        let needSuccessAlert = false
        switch (method.toUpperCase()) {
            case "POST":
                response = await axios.post(link, data, config || undefined);
                break;
            case "PUT":
                needSuccessAlert = true
                response = await axios.put(link, data);
                break;
            case "DELETE":
                needSuccessAlert = true
                response = await axios.delete(link);
                break;
            case "GET":
            default:
                response = await axios.get(link, config || undefined);
                break;
        }

        // Успешный результат

        if (needSuccessAlert)
            alertStore.show("Операция выполнена успешно", "success");

        return response;
    } catch (error: any) {
        // Проверка на 419 ошибку
        if (error?.response?.status === 419) {
            alertStore.show("Сессия истекла, страница будет перезагружена", "info");
            window.location.reload();
            // @ts-ignore
            return Promise.reject("Сессия истекла");
        }

        // Ошибка
        alertStore.show(`Ошибка: ${error?.message || "Неизвестная ошибка"}`);
        // @ts-ignore
        return Promise.reject(error);
    }
}
