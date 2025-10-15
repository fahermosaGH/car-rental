import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly baseUrl = 'http://127.0.0.1:8000/api';

  constructor(private http: HttpClient) {}

  getVehicles(): Observable<any> {
    return this.http.get(`${this.baseUrl}/vehicles`);
  }

  getAvailability(pickup: number, returnLoc: number, start: string, end: string): Observable<any> {
    return this.http.get(`${this.baseUrl}/availability`, {
      params: { pickup, return: returnLoc, start, end }
    });
  }
}
