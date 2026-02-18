import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface StockRowDto {
  id: number;
  quantity: number;
  vehicle: {
    id: number;
    brand: string | null;
    model: string | null;
    year: number | null;
    isActive: boolean;
  } | null;
  location: {
    id: number;
    name: string;
    city: string | null;
    isActive: boolean;
  } | null;
}

export interface StockUpsertPayload {
  vehicleId: number;
  locationId: number;
  quantity: number; // (hoy el backend lo ignora y deriva desde unidades, pero lo dejamos por compatibilidad)
}

@Injectable({ providedIn: 'root' })
export class AdminStockService {
  private readonly baseUrl = 'http://127.0.0.1:8000/api/admin/stock';

  constructor(private http: HttpClient) {}

  list(params?: { locationId?: number; vehicleId?: number }): Observable<StockRowDto[]> {
    const q: string[] = [];
    if (params?.locationId) q.push(`locationId=${params.locationId}`);
    if (params?.vehicleId) q.push(`vehicleId=${params.vehicleId}`);
    const url = q.length ? `${this.baseUrl}?${q.join('&')}` : this.baseUrl;

    return this.http.get<StockRowDto[]>(url);
  }

  upsert(payload: StockUpsertPayload): Observable<any> {
    return this.http.post<any>(this.baseUrl, payload);
  }

  update(id: number, quantity: number): Observable<any> {
    return this.http.put<any>(`${this.baseUrl}/${id}`, { quantity });
  }

  // ✅ Recalcula TODO el stock desde unidades con patente
  rebuild(): Observable<any> {
    return this.http.post<any>(`${this.baseUrl}/rebuild`, {});
  }
}

