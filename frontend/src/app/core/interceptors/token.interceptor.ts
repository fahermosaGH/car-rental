import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service'; // OJO: ruta relativa a *este* archivo
import { environment } from '../../../environments/environment';

export const authTokenInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const token = auth.token;

  // ¿La request va a nuestra API?
  // Cubre tanto absoluta (http://127.0.0.1:8000/api/...) como relativa (/api/...)
  const apiBase = environment.apiUrl.replace(/\/+$/, ''); // sin / final
  const isApiAbsolute = req.url.startsWith(apiBase + '/');
  const isApiRelative = req.url.startsWith('/api/');
  const isApiCall = isApiAbsolute || isApiRelative;

  // Log de depuración útil (lo podés quitar luego)
  // Miralo en DevTools > Console cuando confirmás la reserva
  // eslint-disable-next-line no-console
  console.debug('[authTokenInterceptor] url=', req.url, 
                'isApiCall=', isApiCall, 
                'hasToken=', Boolean(token));

  if (isApiCall && token) {
    const cloned = req.clone({
      setHeaders: { Authorization: `Bearer ${token}` }
    });
    return next(cloned);
  }
  return next(req);
};