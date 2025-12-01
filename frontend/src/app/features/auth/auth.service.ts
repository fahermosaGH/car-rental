import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';

const TOKEN_KEY = 'auth_token';
const RETURN_URL_KEY = 'auth_return_url';

export interface LoginPayload {
  email: string;
  password: string;
}

export interface LoginResponse {
  token: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.apiUrl; // ej: http://localhost:8000/api
  private loggedIn$ = new BehaviorSubject<boolean>(this.hasToken());

  constructor(private http: HttpClient, private router: Router) {}

  /** Saber si hay token */
  isLoggedIn(): Observable<boolean> {
    return this.loggedIn$.asObservable();
  }

  /** Login */
  login(payload: LoginPayload): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.apiUrl}login_check`, payload)
      .pipe(
        tap((res) => {
          localStorage.setItem(TOKEN_KEY, res.token);
          this.loggedIn$.next(true);
        })
      );
  }

  /** Logout */
  logout() {
    localStorage.removeItem(TOKEN_KEY);
    this.loggedIn$.next(false);
    this.router.navigate(['/auth/login']);
  }

  /** Obtener token */
  getToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  }

  /** Saber si hay token almacenado */
  private hasToken(): boolean {
    return !!localStorage.getItem(TOKEN_KEY);
  }
}
