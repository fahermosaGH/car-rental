import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AtencionClienteComponent } from './atencion-cliente';

describe('AtencionClienteComponent', () => {
  let component: AtencionClienteComponent;
  let fixture: ComponentFixture<AtencionClienteComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AtencionClienteComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(AtencionClienteComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
