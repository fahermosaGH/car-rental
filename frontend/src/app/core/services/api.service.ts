import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  // 🔹 Crear una reserva
  crearReserva(payload: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/reservations`, payload);
  }

  // 🔹 Listar reservas (por ahora solo para probar)
  obtenerReservas(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/reservations`);
  }
}
