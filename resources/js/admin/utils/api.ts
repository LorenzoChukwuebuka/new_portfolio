import axios, { AxiosInstance, AxiosRequestConfig } from 'axios';

class ApiService {
    private api: AxiosInstance;

    constructor() {
        this.api = axios.create({
            baseURL: '/api',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        });

        // Add auth token to requests
        this.api.interceptors.request.use((config) => {
            const token = localStorage.getItem('auth_token');
            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }
            return config;
        });

        // Handle 401 errors
        this.api.interceptors.response.use(
            (response) => response,
            (error) => {
                if (error.response?.status === 401) {
                    localStorage.removeItem('auth_token');
                    window.location.href = '/admin';
                }
                return Promise.reject(error);
            }
        );
    }

    // Auth
    async login(email: string, password: string) {
        const { data } = await this.api.post('/admin/login', { email, password });
  
        if (data.access_token) {
            localStorage.setItem('auth_token', data.access_token);
        }
        return data;
    }

    async logout() {
        await this.api.post('/admin/logout');
        localStorage.removeItem('auth_token');
    }

    isAuthenticated(): boolean {
        return !!localStorage.getItem('auth_token');
    }

    // Generic requests
    get(url: string, config?: AxiosRequestConfig) {
        return this.api.get(url, config);
    }

    post(url: string, data?: any, config?: AxiosRequestConfig) {
        return this.api.post(url, data, config);
    }

    put(url: string, data?: any, config?: AxiosRequestConfig) {
        return this.api.put(url, data, config);
    }

    delete(url: string, config?: AxiosRequestConfig) {
        return this.api.delete(url, config);
    }

    // File upload
    uploadFile(url: string, formData: FormData) {
        return this.api.post(url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
    }
}

export default new ApiService();