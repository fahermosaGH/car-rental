import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface AdminLocationDto {
  id: number;
  name: string;
  city: string | null;
  address: string | null;
  latitude: number | null;
  longitude: number | null;
  isActive: boolean;
}

export interface AdminLocationCreateUpdate {
  name?: string;
  city?: string | null;
  address?: string | null;
  latitude?: number | null;
  longitude?: number | null;
  isActive?: boolean;
}

@Injectable({ providedIn: 'root' })
export class AdminLocationsService {
  private readonly baseUrl = 'http://127.0.0.1:8000/api/admin/locations';

  constructor(private http: HttpClient) {}

  list(): Observable<AdminLocationDto[]> {
    return this.http.get<AdminLocationDto[]>(this.baseUrl);
  }

  create(payload: AdminLocationCreateUpdate): Observable<AdminLocationDto> {
    return this.http.post<AdminLocationDto>(this.baseUrl, payload);
  }

  update(id: number, payload: AdminLocationCreateUpdate): Observable<AdminLocationDto> {
    return this.http.put<AdminLocationDto>(`${this.baseUrl}/${id}`, payload);
  }

  deactivate(id: number): Observable<{ ok: boolean; id: number; isActive: boolean }> {
    return this.http.delete<{ ok: boolean; id: number; isActive: boolean }>(`${this.baseUrl}/${id}`);
  }
}
