import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { CotizarService } from '../../services/cotizar.service';
import { VehicleOption } from '../../models/quote';

@Component({
  selector: 'app-resultados',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './resultados.component.html',
  styleUrls: ['./resultados.component.css']
})
export class ResultadosComponent implements OnInit {
  resultados: VehicleOption[] = [];
  dias: number = 0;

  constructor(
    private route: ActivatedRoute,
    private cotizarService: CotizarService
  ) {}

  ngOnInit() {
    this.route.queryParamMap.subscribe(params => {
      const start = new Date(params.get('fromISO') || '');
      const end = new Date(params.get('toISO') || '');
      this.dias = Math.max(1, (end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));

      this.cotizarService.buscarVehiculos().subscribe(data => {
        console.log('🚗 Vehículos recibidos:', data);
        this.resultados = data;
      });
    });
  }

  calcularTotal(rate: number): number {
    return rate * this.dias;
  }
}
