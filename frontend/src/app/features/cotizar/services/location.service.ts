import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../../environments/environment';

export interface Location {
  id: number;
  name: string;
  address: string;
  city: string;
  latitude: number;
  longitude: number;
}

@Injectable({ providedIn: 'root' })
export class LocationService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  obtenerSucursales(): Observable<Location[]> {
    return this.http.get<Location[]>(`${this.apiUrl}/locations`).pipe(
      map((data) =>
        data.map((l) => ({
          id: l.id,
          name: l.name,
          address: l.address,
          city: l.city,
          latitude: l.latitude,
          longitude: l.longitude,
        }))
      )
    );
  }
}
