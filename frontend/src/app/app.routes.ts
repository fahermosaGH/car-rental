import { Routes } from '@angular/router';

export const routes: Routes = [
  { path: '', redirectTo: 'cotizar', pathMatch: 'full' },
  {
    path: 'cotizar',
    children: [
      {
        path: '',
        loadComponent: () =>
          import('./features/cotizar/pages/buscador/buscador.component')
            .then(m => m.BuscadorComponent),
      },
      {
        path: 'resultados',
        loadComponent: () =>
          import('./features/cotizar/pages/resultados/resultados.component')
            .then(m => m.ResultadosComponent),
      },
      {
        path: 'detalle/:id',
        loadComponent: () =>
          import('./features/cotizar/pages/detalle/detalle.component')
            .then(m => m.DetalleComponent),
      },
      {
        path: 'confirmacion/:id',
        loadComponent: () =>
          import('./features/cotizar/pages/confirmacion/confirmacion.component')
            .then(m => m.ConfirmacionComponent),
      },
    ],
  },
  { path: '**', redirectTo: 'cotizar' },
];

import { Routes } from '@angular/router';

export const routes: Routes = [
  { path: '', redirectTo: 'cotizar', pathMatch: 'full' },
  {
    path: 'cotizar',
    children: [
      { path: '', loadComponent: () => import('./features/cotizar/pages/buscador/buscador.component').then(m => m.BuscadorComponent) },
      { path: 'resultados', loadComponent: () => import('./features/cotizar/pages/resultados/resultados.component').then(m => m.ResultadosComponent) },
    ],
  },
  { path: '**', redirectTo: 'cotizar' },
];