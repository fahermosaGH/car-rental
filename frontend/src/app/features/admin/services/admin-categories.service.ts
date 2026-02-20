import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface AdminCategoryDto {
  id: number;
  name: string;
  isActive?: boolean;
}

@Injectable({ providedIn: 'root' })
export class AdminCategoriesService {
  private readonly baseUrl = 'http://127.0.0.1:8000/api/admin/categories';

  constructor(private http: HttpClient) {}

  list(params?: { includeInactive?: boolean }): Observable<AdminCategoryDto[]> {
    const url = params?.includeInactive ? `${this.baseUrl}?includeInactive=1` : this.baseUrl;
    return this.http.get<AdminCategoryDto[]>(url);
  }
}