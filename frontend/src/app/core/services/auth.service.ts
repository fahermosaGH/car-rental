import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import {
  BehaviorSubject,
  Observable,
  catchError,
  map,
  of,
  switchMap,
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

export interface AuthUser {
  email: string;
  roles: string[];
}

export interface ProfileResponse {
  email: string;
  firstName: string;
  lastName: string;
  createdAt: string;

  phone: string | null;
  documentNumber: string | null;
  birthDate: string | null;
  address: string | null;
  licenseNumber: string | null;
  licenseCountry: string | null;
  licenseExpiry: string | null;

  profileComplete: boolean;
}

export interface ProfileUpdatePayload {
  firstName: string;
  lastName: string;
  phone: string | null;
  documentNumber: string | null;
  birthDate: string | null;
  address: string | null;
  licenseNumber: string | null;
  licenseCountry: string | null;
  licenseExpiry: string | null;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private apiUrl = environment.apiUrl;

  readonly authChanges = new BehaviorSubject<boolean>(this.isLoggedIn());

  private readonly userSubject = new BehaviorSubject<AuthUser | null>(null);
  readonly user$ = this.userSubject.asObservable();

  constructor(private http: HttpClient, private router: Router) {
    // ✅ Solo intenta /me si hay token
    if (this.token) {
      this.loadMe().subscribe({
        next: () => {},
        error: () => this.userSubject.next(null),
      });
    }
  }

  // =========================
  // Helpers usuario/rol
  // =========================
  get currentUser(): AuthUser | null {
    return this.userSubject.value;
  }

  hasRole(role: string): boolean {
    const u = this.userSubject.value;
    return !!u?.roles?.includes(role);
  }

  isAdmin(): boolean {
    return this.hasRole('ROLE_ADMIN');
  }

  setCurrentUser(user: AuthUser | null): void {
    this.userSubject.next(user);
  }

  // =========================
  // Token helpers
  // =========================
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
    return !!this.token;
  }

  logout(): void {
    this.token = null;
    this.userSubject.next(null);
  }

  // =========================
  // Return URL helpers
  // =========================
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

  requireLoginOrRedirect(currentUrl: string): boolean {
    if (this.isLoggedIn()) return true;
    this.setReturnUrl(currentUrl);
    this.router.navigate(['/auth/login'], {
      queryParams: { returnUrl: currentUrl },
    });
    return false;
  }

  // =========================
  // API calls
  // =========================

  /**
   * ✅ Login: guarda token y luego carga /me (con token sí o sí)
   */
  login(email: string, password: string): Observable<MeResponse> {
    return this.http
      .post<AuthResponse>(`${this.apiUrl}/login_check`, { email, password })
      .pipe(
        tap((res) => (this.token = res.token)),
        switchMap(() => this.meAuthed()),
        tap((me) => this.userSubject.next({ email: me.email, roles: me.roles })),
        catchError((err) => throwError(() => err))
      );
  }

  register(payload: RegisterPayload): Observable<{ message: string }> {
    return this.http
      .post<{ message: string }>(`${this.apiUrl}/register`, payload)
      .pipe(catchError((err) => throwError(() => err)));
  }

  resetPassword(email: string, newPassword: string) {
    return this.http.post(`${this.apiUrl}/forgot-password`, {
      email,
      newPassword,
    });
  }

  /**
   * ✅ Seguro: si NO hay token, NO pega al backend y devuelve null.
   * Esto elimina el 401 rojo en pantallas públicas.
   */
  me(): Observable<MeResponse | null> {
    if (!this.token) {
      return of(null);
    }
    return this.meAuthed().pipe(
      catchError(() => of(null)) // token vencido o inválido -> no explota la UI
    );
  }

  /**
   * ✅ Solo interno: requiere token sí o sí (para login / flujo protegido)
   */
  private meAuthed(): Observable<MeResponse> {
    const headers = new HttpHeaders({ Authorization: `Bearer ${this.token}` });
    return this.http
      .get<MeResponse>(`${this.apiUrl}/me`, { headers })
      .pipe(catchError((err) => throwError(() => err)));
  }

  /**
   * ✅ Público: refresh de user. Si no hay token, limpia y termina.
   */
  loadMe(): Observable<MeResponse | null> {
    return this.me().pipe(
      tap((me) => {
        if (!me) {
          this.userSubject.next(null);
          return;
        }
        this.userSubject.next({ email: me.email, roles: me.roles });
      })
    );
  }

  getProfile(): Observable<ProfileResponse> {
    const headers = this.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.token}` })
      : undefined;

    return this.http
      .get<ProfileResponse>(`${this.apiUrl}/profile/me`, { headers })
      .pipe(catchError((err) => throwError(() => err)));
  }

  updateProfile(payload: ProfileUpdatePayload): Observable<ProfileResponse> {
    const headers = this.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.token}` })
      : undefined;

    return this.http
      .put<ProfileResponse>(`${this.apiUrl}/profile`, payload, { headers })
      .pipe(catchError((err) => throwError(() => err)));
  }
}