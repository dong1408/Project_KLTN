export interface ApiResponse<T> {
    data: T,
    message: string,
    httpCode?: number
}

export interface PagingResponse<T> {
    totalPage: number,
    totalRecord: number,
    data: T
}