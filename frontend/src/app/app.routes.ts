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