// frontend/src/app/core/interceptors/token.interceptor.ts
import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { environment } from '../../../environments/environment';

/**
 * Interceptor que agrega el header Authorization: Bearer <token>
 * SOLO cuando la request va a nuestra API (http://localhost:8000/api o /api/...).
 */
export const authTokenInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const token = auth.token;

  // Normalizamos base de la API (sin "/" final)
  const apiBase = environment.apiUrl.replace(/\/+$/, '');

  const isApiAbsolute = req.url.startsWith(apiBase + '/');
  const isApiRelative = req.url.startsWith('/api/');
  const isApiCall = isApiAbsolute || isApiRelative;

  // (debug opcional)
  // console.debug('[authTokenInterceptor] url=', req.url,
  //               'isApiCall=', isApiCall,
  //               'hasToken=', Boolean(token));

  if (isApiCall && token) {
    const cloned = req.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`,
      },
    });
    return next(cloned);
  }

  return next(req);
};