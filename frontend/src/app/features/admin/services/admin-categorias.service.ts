import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface AdminCategoryRow {
  id: number;
  name: string;
  description: string | null;
  dailyPrice: number | null;
  isActive: boolean;
  vehiclesCount: number;
}

export interface AdminCategoryListParams {
  showInactive?: boolean;
  search?: string;
}

export interface AdminCategoryUpsertPayload {
  name: string;
  description?: string | null;
  dailyPrice?: number | string | null;
  isActive?: boolean;
}

@Injectable({ providedIn: 'root' })
export class AdminCategoriasService {
  private base = '/api/admin/categories';

  constructor(private http: HttpClient) {}

  list(params?: AdminCategoryListParams): Observable<AdminCategoryRow[]> {
    let hp = new HttpParams();
    if (params?.showInactive) hp = hp.set('showInactive', '1');
    if (params?.search) hp = hp.set('search', params.search);
    return this.http.get<AdminCategoryRow[]>(this.base, { params: hp });
  }

  create(payload: AdminCategoryUpsertPayload): Observable<AdminCategoryRow> {
    return this.http.post<AdminCategoryRow>(this.base, payload);
  }

  update(id: number, payload: AdminCategoryUpsertPayload): Observable<AdminCategoryRow> {
    return this.http.put<AdminCategoryRow>(`${this.base}/${id}`, payload);
  }

  toggle(id: number): Observable<{ id: number; isActive: boolean }> {
    return this.http.patch<{ id: number; isActive: boolean }>(`${this.base}/${id}/toggle`, {});
  }

  remove(id: number): Observable<{ ok: boolean }> {
    return this.http.delete<{ ok: boolean }>(`${this.base}/${id}`);
  }
}