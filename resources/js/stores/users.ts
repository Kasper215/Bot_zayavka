import {defineStore} from 'pinia'
import {makeAxiosFactory} from './utillites/makeAxiosFactory'
import {useAlertStore} from './utillites/useAlertStore'
import axios, {AxiosError} from "axios";

export interface User {
    id: number
    name: string
    email: string
    role: number
    email_verified_at?: string
    blocked_at?: string
    blocked_message?: string
}

const path: string = '/admin/users'


export const useUsersStore = defineStore('users', {
    state: () => ({
        items: [] as User[],
        self: null,
        loading: false,
        progress: 0,
        error: null as string | null,
    }),
    getters: {
        byId: (s) => (id: number) => s.items.find(u => u.id === id),
    },
    actions: {
        setRole(role) {
            this.self.role = role
        },
        async fetchSelf() {
            this.loading = true
            this.error = null
            try {
                const {data} = await makeAxiosFactory(`${path}/self`, 'POST')
                this.self = data
            } catch (e: any) {
                this.error = e?.message || 'Failed to load agents'
            } finally {
                this.loading = false
            }
            return true
        },
        async fetchPublicSelf() {
            this.loading = true;
            try {
                const {data} = await axios.get('/api/public/me');
                this.self = data;
            } catch (e) {
                this.self = null;
            } finally {
                this.loading = false;
            }
        },
        // @ts-ignore
        async fetchFiltered(page = 1) {
            this.loading = true
            this.error = null
            try {
                const params = new URLSearchParams()

                // фильтры
                // @ts-ignore
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '' && value !== false) {
                        params.append(key, String(value))
                    }
                })

                // сортировка
                params.append('sort_field', this.sort.field)
                params.append('sort_direction', this.sort.direction)

                // пагинация
                params.append('page', String(page))

                const {data} = await makeAxiosFactory(`${path}?${params.toString()}`, 'GET')
                this.items = data.data
                this.pagination = data
            } catch (error: any) {
                this.error = error.response?.data?.message ?? 'Ошибка загрузки пользователей'
            } finally {
                this.loading = false
            }
        },

        setFilters(filters: Record<string, any>) {
            this.filters = filters
        },

        setSort(field: string, direction: 'asc' | 'desc') {
            this.sort = {field, direction}
        },
        // @ts-ignore
        async fetchAll() {
            this.loading = true
            this.error = null
            try {
                const {data} = await makeAxiosFactory(`${path}`, 'GET')
                this.items = data.data
                console.log("data=>", data)
            } catch (e: any) {
                this.error = e?.message || 'Failed to load users'
            } finally {
                this.loading = false
            }
        },
        // @ts-ignore
        async fetchAllByPage(page = 1) {
            const {data} = await makeAxiosFactory(`${path}?page=${page}`, 'GET')
            this.items = data.data
            this.pagination = data
        },
        // @ts-ignore
        async fetchByUrl(url: string) {
            const {data} = await makeAxiosFactory(url, 'GET')
            this.items = data.data
            this.pagination = data
        },
        async fetchOne(id: number) {
            try {
                const {data} = await makeAxiosFactory(`${path}/${id}`, 'GET')
                return data as User
            } catch (e: any) {
                this.error = e?.message || 'Failed to load user'
                throw e
            }
        },
        async uploadAnonymousForm(form: object, files: File[] = []): Promise<any> {
            this.loading = true
            this.error = null

            const alertStore = useAlertStore()

            try {
                const formData = new FormData()

                Object.entries(form).forEach(([key, value]) => {
                    formData.append(key, String(value));
                });

                files.forEach((file, index) => {
                    formData.append(`files[${index}]`, file);
                });

                const {data} = await makeAxiosFactory(`/api/public/submit-form`, 'POST', formData)
                alertStore.show("Ваши данные успешно отправлены. Пройдите к оплате!", "success")
                return data;

            } catch (err) {
                const error = err as AxiosError<{ message?: string }>
                this.error = error.response?.data?.message || 'Ошибка при отправке данных'
                alertStore.show(this.error, "error")
            } finally {
                this.loading = false
            }
        },
        async submitPayment(form: { lead_id: number, method: string, screenshot: File }): Promise<any> {
            this.loading = true
            this.error = null

            const alertStore = useAlertStore()

            try {
                const formData = new FormData()
                formData.append('lead_id', String(form.lead_id))
                formData.append('method', form.method)
                formData.append('screenshot', form.screenshot)

                const {data} = await makeAxiosFactory(`/api/public/submit-payment`, 'POST', formData)
                alertStore.show("Оплата отправлена на проверку!", "success")
                return data;

            } catch (err) {
                const error = err as AxiosError<{ message?: string }>
                this.error = error.response?.data?.message || 'Ошибка при отправке оплаты'
                alertStore.show(this.error, "error")
            } finally {
                this.loading = false
            }
        },
        async uploadForm(form: object): Promise<void> {
            this.loading = true
            this.error = null

            const alertStore = useAlertStore()

            alertStore.show("Ваши данные успешно сохранены!","success")
            try {
                const formData = new FormData()

                Object.entries(form).forEach(([key, value]) => {
                    formData.append(key, value);
                });

             //  formData.append('file', file)

               // axios.defaults.adapter = "xhr";

                const {data} = await makeAxiosFactory(`${path}/send-form`, 'POST', formData)

            } catch (err) {
                const error = err as AxiosError<{ message?: string }>
                this.error = error.response?.data?.message || 'Ошибка при загрузке данных'
                alertStore.show(this.error, "error")
            } finally {
                this.loading = false
            }
        },
        async uploadFormWithVideo(form: object, file: File): Promise<void> {
            this.loading = true
            this.error = null

            const alertStore = useAlertStore()

            alertStore.show("Загрузка файла началась")
            try {
                const formData = new FormData()

                Object.entries(form).forEach(([key, value]) => {
                    formData.append(key, value);
                });

                formData.append('file', file)

                axios.defaults.adapter = "xhr";

                const {data} = await axios.post<{ message: string }>(
                    `${path}/send-video`,
                    formData,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        },
                        onUploadProgress: (e) => {
                            if (e.total) {
                                this.progress =
                                    Math.round((e.loaded * 100) / e.total);
                            }
                        },
                    }
                )

                let message = data.message || 'Файл успешно загружен'
                alertStore.show(message, "success")
            } catch (err) {
                const error = err as AxiosError<{ message?: string }>
                this.error = error.response?.data?.message || 'Ошибка при загрузке файла'
                alertStore.show(this.error, "error")
            } finally {
                this.loading = false
            }
        },
        async createPrimary(payload: object) {
            const {data} = await makeAxiosFactory(`${path}/primary`, 'POST', payload)
            this.items.push(data)
            return data as User
        },
        async create(payload: Omit<User, 'id'>) {
            const {data} = await makeAxiosFactory(`${path}`, 'POST', payload)
            this.items.push(data)
            return data as User
        },

        async update(id: number, payload: object) {
            const {data} = await makeAxiosFactory(`${path}/${id}`, 'PUT', payload)
            const idx = this.items.findIndex(u => u.id === id)
            if (idx !== -1) this.items[idx] = data
            return data as User
        },
        // @ts-ignore
        async remove(id: number) {

            await makeAxiosFactory(`${path}/${id}`, 'DELETE')
            this.items = this.items.filter(u => u.id !== id)
        },
        async block(id: number, blocked_message?: string) {
            const {data} = await makeAxiosFactory(`${path}/${id}/block`, 'PATCH', {blocked_message})
            const idx = this.items.findIndex(u => u.id === id)
            if (idx !== -1) this.items[idx] = data
            return data as User
        },

        async unblock(id: number) {
            const {data} = await makeAxiosFactory(`${path}/${id}/unblock`, 'PATCH')
            const idx = this.items.findIndex(u => u.id === id)
            if (idx !== -1) this.items[idx] = data
            return data as User
        },
    },
})
