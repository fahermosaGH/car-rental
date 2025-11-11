import { Component, Input, OnChanges, SimpleChanges, OnInit } from '@angular/core';
import * as L from 'leaflet';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-mapa-sucursales',
  templateUrl: './mapa-sucursales.component.html',
  styleUrls: ['./mapa-sucursales.component.css']
})
export class MapaSucursalesComponent implements OnInit, OnChanges {
  @Input() selectedLocationId?: number; // 👈 Nuevo input
  private map!: L.Map;
  private markers: Record<number, L.Marker> = {};

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
  setTimeout(() => {
    this.map = L.map('map').setView([-31.6, -60.7], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(this.map);

    this.cargarSucursales();
  }, 0);
}

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['selectedLocationId'] && this.selectedLocationId) {
      this.centrarEnSucursal(this.selectedLocationId);
    }
  }

  cargarSucursales() {
    this.http.get<any[]>('http://127.0.0.1:8000/api/locations').subscribe(data => {
      data.forEach(loc => {
        const marker = L.marker([loc.latitude, loc.longitude]).addTo(this.map);
        marker.bindPopup(`
          <b>${loc.name}</b><br>
          ${loc.address}<br>
          <a href="https://www.google.com/maps?q=${loc.latitude},${loc.longitude}" target="_blank">📍 Ver en Google Maps</a>
        `);
        this.markers[loc.id] = marker;
      });
    });
  }

  centrarEnSucursal(id: number) {
    const marker = this.markers[id];
    if (marker) {
      this.map.setView(marker.getLatLng(), 12, { animate: true });
      marker.openPopup();
    }
  }
}
