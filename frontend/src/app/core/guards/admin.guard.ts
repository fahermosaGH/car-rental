import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { AuthService } from '../services/auth.service';

export const adminGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  // Si no hay token -> afuera
  if (!auth.isLoggedIn()) {
    router.navigate(['/auth/login']);
    return false;
  }

  // Si ya tenemos user cargado, usamos eso (evita /me innecesario)
  const current = auth.currentUser;
  if (current) {
    if (current.roles?.includes('ROLE_ADMIN')) return true;
    router.navigate(['/']);
    return false;
  }

  // Si no está cargado, pedimos /me y validamos
  return auth.loadMe().pipe(
    map((me) => {
      // ✅ me nunca debería ser null acá, pero igual lo blindamos
      if (me && Array.isArray(me.roles) && me.roles.includes('ROLE_ADMIN')) {
        return true;
      }
      router.navigate(['/']);
      return false;
    }),
    catchError(() => {
      router.navigate(['/auth/login']);
      return of(false);
    })
  );
};