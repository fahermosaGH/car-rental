import { Injectable } from '@angular/core';
import { HttpClient, HttpParams, HttpHeaders } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { VehicleOption } from '../models/quote';
import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/services/auth.service';

export interface VehicleRatingsResponse {
  vehicleId: number;
  ratingAvg: number | null;
  ratingCount: number;
  items: Array<{
    rating: number | null;
    comment: string | null;
    endAt: string | null; // viene del backend como endAt
  }>;
}

@Injectable({ providedIn: 'root' })
export class CotizarService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  // -------------------------------------------------------------
  // Normalizador de categoría
  // -------------------------------------------------------------
  private normalizeCategory(cat: any): string {
    if (!cat) return 'Sin categoría';
    if (typeof cat === 'string') return cat.trim();
    if (typeof cat === 'object' && cat.name) return String(cat.name).trim();
    return String(cat).trim();
  }

  // -------------------------------------------------------------
  // VEHÍCULOS
  // -------------------------------------------------------------
  buscarVehiculos(): Observable<VehicleOption[]> {
    return this.http.get<any[]>(`${this.apiUrl}/vehicles`).pipe(
      map((data) =>
        data.map((v) => {
          const category = this.normalizeCategory(v.category);

          return {
            id: v.id,
            category,
            brand: v.brand,
            model: v.model,
            name: `${v.brand} ${v.model}`,
            year: v.year,
            seats: v.seats,
            transmission: v.transmission,
            dailyRate: parseFloat(v.dailyRate ?? 0),
            img: 'https://picsum.photos/seed/' + v.model + '/400/220',
            fuel: 'Nafta',
            description: `${v.brand} ${v.model} (${category})`,
            unitsAvailable: typeof v.unitsAvailable === 'number' ? v.unitsAvailable : undefined,

            // ratings summary
            ratingAvg: typeof v.ratingAvg === 'number' ? v.ratingAvg : null,
            ratingCount: typeof v.ratingCount === 'number' ? v.ratingCount : 0,
          };
        })
      )
    );
  }

  obtenerVehiculoPorId(id: number): Observable<VehicleOption | undefined> {
    return this.buscarVehiculos().pipe(map((vehiculos) => vehiculos.find((v) => v.id === id)));
  }

  // -------------------------------------------------------------
  // SUCURSALES
  // -------------------------------------------------------------
  obtenerSucursales(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/locations`).pipe(
      map((data) =>
        data.map((s) => ({
          id: s.id,
          nombre: s.name || s.nombre,
          ciudad: s.city || '',
          direccion: s.address || '',
        }))
      )
    );
  }

  // -------------------------------------------------------------
  // DISPONIBLES
  // -------------------------------------------------------------
  getAvailableVehicles(params: {
    pickupLocationId: number;
    startAt: string;
    endAt: string;
    category?: string;
  }): Observable<VehicleOption[]> {
    let httpParams = new HttpParams()
      .set('pickupLocationId', String(params.pickupLocationId))
      .set('startAt', params.startAt)
      .set('endAt', params.endAt);

    if (params.category && params.category.trim() !== '') {
      httpParams = httpParams.set('category', params.category.trim());
    }

    return this.http.get<any[]>(`${this.apiUrl}/vehicles/available`, { params: httpParams }).pipe(
      map((data) =>
        data.map((v) => ({
          id: v.id,
          category: v.category ?? 'Sin categoría',
          brand: v.brand,
          model: v.model,
          name: `${v.brand} ${v.model}`,
          year: v.year,
          seats: v.seats,
          transmission: v.transmission,
          dailyRate: parseFloat(v.dailyRate ?? 0),
          img: 'https://picsum.photos/seed/' + v.model + '/400/220',
          description: `${v.brand} ${v.model} (${v.category ?? 'Sin categoría'})`,
          fuel: 'Nafta',
          unitsAvailable: v.unitsAvailable,
          branchStock: v.branchStock,

          ratingAvg: typeof v.ratingAvg === 'number' ? v.ratingAvg : null,
          ratingCount: typeof v.ratingCount === 'number' ? v.ratingCount : 0,
        }))
      )
    );
  }

  // -------------------------------------------------------------
  // RATINGS DETALLE
  // -------------------------------------------------------------
  getVehicleRatings(vehicleId: number, limit = 6): Observable<VehicleRatingsResponse> {
    return this.http.get<VehicleRatingsResponse>(`${this.apiUrl}/vehicles/${vehicleId}/ratings`, {
      params: new HttpParams().set('limit', String(limit)),
    });
  }

  // -------------------------------------------------------------
  // CHECK AVAILABILITY
  // -------------------------------------------------------------
  checkAvailability(params: {
    vehicleId: number;
    pickupLocationId: number;
    startAt: string;
    endAt: string;
  }): Observable<{ available: boolean; message: string }> {
    const httpParams = new HttpParams()
      .set('vehicle', String(params.vehicleId))
      .set('pickup', String(params.pickupLocationId))
      .set('start', params.startAt)
      .set('end', params.endAt);

    return this.http.get<{ available: boolean; message: string }>(`${this.apiUrl}/check-availability`, {
      params: httpParams,
    });
  }

  // -------------------------------------------------------------
  // ✅ RESERVAS (nuevo payload: pricing)
  // -------------------------------------------------------------
  crearReserva(payload: {
    vehicleId: number;
    pickupLocationId: number;
    dropoffLocationId: number;
    startAt: string;
    endAt: string;
    pricing: {
      insuranceCode: string | null;
      extras: Array<{
        code: string;
        quantity: number;
        price: number;
        billing: 'per_day' | 'per_reservation';
      }>;
    };
  }): Observable<{ message: string; id: number; pricing?: any }> {
    const headers = this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;

    return this.http.post<{ message: string; id: number; pricing?: any }>(`${this.apiUrl}/reservations`, payload, {
      headers,
    });
  }

  getReservationById(id: number) {
    const headers = this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;

    return this.http.get<{
      id: number;
      vehicleName: string;
      category: string | null;
      pickupLocationName: string;
      dropoffLocationName: string;
      startAt: string;
      endAt: string;
      totalPrice: string | null;
      status: string;
      extras: { name: string; price: string }[];
      rating?: number;
      ratingComment?: string;
    }>(`${this.apiUrl}/reservations/${id}`, { headers });
  }

  enviarComprobante(reservationId: number, email: string) {
    const headers = this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;

    return this.http.post<{ message: string; email: string }>(
      `${this.apiUrl}/reservations/${reservationId}/send-voucher`,
      { email },
      { headers }
    );
  }

  calificarReserva(reservationId: number, payload: { rating: number; comment: string }) {
    const headers = this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;

    return this.http.post(`${this.apiUrl}/reservations/${reservationId}/rating`, payload, { headers });
  }
}
