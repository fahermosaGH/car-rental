import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import {
  BehaviorSubject,
  Observable,
  catchError,
  map,
  tap,
  throwError,
} from 'rxjs';
import { environment } from '../../../environments/environment';
import { Router } from '@angular/router';

const TOKEN_KEY = 'auth_token';
const RETURN_URL_KEY = 'auth_return_url';

export interface AuthResponse {
  token: string;
}

export interface MeResponse {
  email: string;
  roles: string[];
}

export interface RegisterPayload {
  firstName: string;
  lastName: string;
  email: string;
  password: string;
}

// 👇 Nuevo: modelo de usuario para el front
export interface AuthUser {
  email: string;
  roles: string[];
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private apiUrl = environment.apiUrl; // ej: http://127.0.0.1:8000/api

  /** Emite true/false cuando cambia el estado de autenticación */
  readonly authChanges = new BehaviorSubject<boolean>(this.isLoggedIn());

  /** Nuevo: emite el usuario actual (email + roles) o null */
  private readonly userSubject = new BehaviorSubject<AuthUser | null>(null);
  readonly user$ = this.userSubject.asObservable();

  constructor(private http: HttpClient, private router: Router) {
    // Si ya hay token al iniciar la app, intentar cargar el usuario
    if (this.isLoggedIn()) {
      this.me().subscribe({
        next: (me) => this.userSubject.next({ email: me.email, roles: me.roles }),
        error: () => this.userSubject.next(null),
      });
    }
  }

  // === Token helpers ===
  get token(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  }

  set token(value: string | null) {
    if (value) {
      localStorage.setItem(TOKEN_KEY, value);
      this.authChanges.next(true);
    } else {
      localStorage.removeItem(TOKEN_KEY);
      this.authChanges.next(false);
    }
  }

  isLoggedIn(): boolean {
    return !!localStorage.getItem(TOKEN_KEY);
  }

  logout(): void {
    this.token = null;
    this.userSubject.next(null); // 👈 limpiar usuario también
  }

  // === Return URL helpers (para volver donde estabas tras loguearte) ===
  setReturnUrl(url: string): void {
    try {
      sessionStorage.setItem(RETURN_URL_KEY, url);
    } catch {}
  }

  consumeReturnUrl(): string | null {
    try {
      const url = sessionStorage.getItem(RETURN_URL_KEY);
      if (url) sessionStorage.removeItem(RETURN_URL_KEY);
      return url;
    } catch {
      return null;
    }
  }

  /**
   * Si no hay sesión, guarda returnUrl y redirige a /auth/login.
   * Devuelve true si ya estás logueado, false si redirigió.
   */
  requireLoginOrRedirect(currentUrl: string): boolean {
    if (this.isLoggedIn()) return true;
    this.setReturnUrl(currentUrl);
    this.router.navigate(['/auth/login'], { queryParams: { returnUrl: currentUrl } });
    return false;
  }

  // === API calls ===

  /** Login contra /api/login_check (LexikJWT). Guarda el token. */
  login(email: string, password: string): Observable<string> {
    return this.http
      .post<AuthResponse>(`${this.apiUrl}/login_check`, { email, password })
      .pipe(
        tap((res) => {
          // guardar token
          this.token = res.token;

          // 👇 una vez que tenemos token, pedimos /me para rellenar user$
          this.me().subscribe({
            next: (me) =>
              this.userSubject.next({ email: me.email, roles: me.roles }),
            error: () => this.userSubject.next(null),
          });
        }),
        map((res) => res.token),
        catchError((err) => throwError(() => err))
      );
  }

  /** Registro simple contra /api/register. */
  register(payload: RegisterPayload): Observable<{ message: string }> {
    return this.http
      .post<{ message: string }>(`${this.apiUrl}/register`, payload)
      .pipe(catchError((err) => throwError(() => err)));
  }

  /** Devuelve el usuario actual desde /api/me. */
  me(): Observable<MeResponse> {
    const headers =
      this.token
        ? new HttpHeaders({ Authorization: `Bearer ${this.token}` })
        : undefined;

    return this.http
      .get<MeResponse>(`${this.apiUrl}/me`, { headers })
      .pipe(catchError((err) => throwError(() => err)));
  }
}


