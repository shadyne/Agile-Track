export interface User {
    id: number
    name: string
    email: string
}

export interface LoginForm {
    email: string
    password: string
    remember: boolean
}

export interface AuthResponse {
    message: string
    token: string
    user: User
}