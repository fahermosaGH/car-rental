import { HttpInterceptorFn } from '@angular/common/http';

export const authTokenInterceptor: HttpInterceptorFn = (req, next) => {
  const token = localStorage.getItem('auth_token'); // 👈 tu key real

  // Si no hay token, seguimos normal
  if (!token) {
    return next(req);
  }

  // Evitamos duplicar si ya viene Authorization
  if (req.headers.has('Authorization')) {
    return next(req);
  }

  const authReq = req.clone({
    setHeaders: {
      Authorization: `Bearer ${token}`,
    },
  });

  return next(authReq);
};
