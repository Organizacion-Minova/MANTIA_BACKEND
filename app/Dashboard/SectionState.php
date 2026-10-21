<?php
//Seccion de estados de cada card del dash board
//
namespace App\Dashboard;

enum SectionState: string
{
    case Ok = 'ok';
    case Empty = 'empty';
    case Unavailable = 'unavailable';
    case Error = 'error';
}