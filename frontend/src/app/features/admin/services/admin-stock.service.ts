import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, catchError, throwError, switchMap, of } from 'rxjs';
import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/services/auth.service';

export type StockRowDto = {
  id: number;
  quantity: number;
  vehicle: { id: number; brand: string; model: string; year?: number | null };
  location: { id: number; name: string; city?: string | null };
};

@Injectable({ providedIn: 'root' })
export class AdminStockService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  private headers(): HttpHeaders {
    return new HttpHeaders({
      Authorization: `Bearer ${this.auth.token}`,
    });
  }

  // LISTADO DE STOCK
  list(): Observable<StockRowDto[]> {
    return this.http.get<StockRowDto[]>(`${this.apiUrl}/admin/stock`, {
      headers: this.headers(),
    });
  }

  // RECALCULAR TODO
  // Intentamos varias rutas (por si tu backend tiene otro nombre)
  rebuildAll(): Observable<{ ok: boolean }> {
    const h = { headers: this.headers() };

    return this.http.post<{ ok: boolean }>(`${this.apiUrl}/admin/stock/rebuild-all`, {}, h).pipe(
      catchError(() =>
        this.http.post<{ ok: boolean }>(`${this.apiUrl}/admin/stock/rebuild`, {}, h).pipe(
          catchError(() =>
            this.http.post<{ ok: boolean }>(`${this.apiUrl}/admin/stock/recalculate`, {}, h)
          )
        )
      )
    );
  }

  // SINCRONIZAR UNA FILA
  // Enviamos vehicleId + locationId y probamos varias rutas comunes.
  syncRow(row: StockRowDto): Observable<{ ok: boolean; quantity?: number }> {
    const h = { headers: this.headers() };

    const payload = {
      vehicleId: row.vehicle?.id,
      locationId: row.location?.id,
    };

    const try1$ = this.http.post<{ ok: boolean; quantity?: number }>(
      `${this.apiUrl}/admin/stock/${row.id}/sync`,
      payload,
      h
    );

    const try2$ = this.http.post<{ ok: boolean; quantity?: number }>(
      `${this.apiUrl}/admin/stock/${row.id}/synchronize`,
      payload,
      h
    );

    const try3$ = this.http.post<{ ok: boolean; quantity?: number }>(
      `${this.apiUrl}/admin/stock/sync/${row.id}`,
      payload,
      h
    );

    return try1$.pipe(
      catchError(() => try2$),
      catchError(() => try3$)
    );
  }
}