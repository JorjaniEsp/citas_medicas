import { Component, inject, OnInit } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { FrmLogin } from '../forms/frm-login/frm-login';

@Component({
  imports: [],
  selector: 'app-login-component',
  styleUrl: './login-component.css',
  templateUrl: './login-component.html',
})
export class LoginComponent implements OnInit {
  private readonly dialogo = inject(MatDialog);

  private mostrarDialogo() {
    const dialogRed =this.dialogo.open(FrmLogin, {
      width:'100%',
      maxWidth: '400px',
      disableClose: true
    });
  }

  ngOnInit(): void {
    this.mostrarDialogo();
  }
}
