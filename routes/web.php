<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\SegurancaController;
use App\Http\Controllers\Admin\ConfiguracaoController;
use App\Http\Controllers\Admin\MedicoController;
use App\Http\Controllers\API\PacienteController;

use App\Http\Controllers\Medico\MedicoDashboardController;
use App\Http\Controllers\Medico\MedicoConfiguracaoController;
use App\Http\Controllers\Medico\MedicoSegurancaController;
use App\Http\Controllers\Medico\MedicoProntuarioController;

use App\Http\Controllers\Admin\EnfermeiroController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Medico\LoginController as MedicoLoginController;
use App\Http\Controllers\Enfermeiro\LoginController as EnfermeiroLoginController;
use App\Http\Controllers\Enfermeiro\DashboardController as EnfermeiroDashboardController;
use App\Http\Controllers\Enfermeiro\ConfiguracaoController as ConfiguracaoEnfermeiroController;
use App\Http\Controllers\Enfermeiro\SegurancaController as SegurancaEnfermeiroController;
use App\Http\Controllers\Enfermeiro\ProntuarioController;

// ----------------------------------------------------------------------------------
// --- 1. ROTAS GERAIS / PÚBLICAS ---
// ----------------------------------------------------------------------------------

// PÁGINA INICIAL
Route::get('/', fn() => view('geral.index'))->name('home');

// LOGIN ADMIN
Route::get('/loginAdm', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/loginAdm', [AdminLoginController::class, 'login']);
Route::post('/logoutAdm', [AdminLoginController::class, 'logout'])->name('admin.logout');


// ----------------------------------------------------------------------------------
// --- 2. ROTAS DO ENFERMEIRO ---
// ----------------------------------------------------------------------------------

Route::prefix('enfermeiro')->name('enfermeiro.')->group(function () {
    Route::get('/login', [EnfermeiroLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [EnfermeiroLoginController::class, 'login'])->name('login.submit');

    Route::middleware('auth:enfermeiro')->group(function () {
        Route::get('/dashboard', [EnfermeiroDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [EnfermeiroLoginController::class, 'logout'])->name('logout');

        Route::get('/perfil', [ConfiguracaoEnfermeiroController::class, 'perfil'])->name('perfil');

        Route::post('/perfil/update', [ConfiguracaoEnfermeiroController::class, 'atualizarPerfil'])->name('perfil.update');

        Route::get('/seguranca', [SegurancaEnfermeiroController::class, 'showAlterarSenhaForm'])->name('seguranca');
        Route::post('/alterar-senha', [SegurancaEnfermeiroController::class, 'alterarSenha'])->name('alterarSenha');

        Route::get('/paciente', fn() => view('enfermeiro.pacientes'))->name('pacientes');
        Route::get('/ajuda', fn() => view('enfermeiro.ajuda'))->name('ajuda');
        Route::get('/prontuarioEnfermeiro', [ProntuarioController::class, 'index'])->name('prontuario');
        Route::get('/prontuario/{id}', [ProntuarioController::class, 'show'])->name('paciente.prontuario'); 


    });
});

// ----------------------------------------------------------------------------------
// --- 3. ROTAS DO MÉDICO  ---
// ----------------------------------------------------------------------------------

// Login Médico
Route::get('/loginMedico', [MedicoLoginController::class, 'showLoginForm'])->name('medico.login');
Route::post('/loginMedico', [MedicoLoginController::class, 'login'])->name('medico.login.submit');
Route::post('/medico/profile/complete', [MedicoLoginController::class, 'completarPerfil'])->name('api.medico.profile.complete');
Route::post('/medico/login/check', [MedicoLoginController::class, 'login'])->name('api.medico.login.check');

Route::middleware('auth')->prefix('medico')->name('medico.')->group(function () {

    Route::get('/dashboard', [MedicoDashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [MedicoLoginController::class, 'logout'])->name('logout');

    Route::get('/perfil', [MedicoConfiguracaoController::class, 'perfil'])->name('perfil');
    Route::post('/perfil/update', [MedicoConfiguracaoController::class, 'atualizarPerfil'])->name('perfil.update');

    Route::get('/seguranca', [MedicoSegurancaController::class, 'showAlterarSenhaForm'])->name('seguranca');
    Route::post('/alterar-senha', [MedicoSegurancaController::class, 'alterarSenha'])->name('alterarSenha');

    Route::get('/prontuario', [MedicoProntuarioController::class, 'index'])->name('prontuario');
    Route::get('/prontuario/{id}', [MedicoProntuarioController::class, 'show'])->name('paciente.prontuario');

    Route::get('/ajuda', fn() => view('medico.ajudaMedico'))->name('ajuda');
});

// ----------------------------------------------------------------------------------
// --- 4. PAINEL ADMIN (PROTEGIDO) ---
// ----------------------------------------------------------------------------------
Route::middleware('auth:admin')->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');


    Route::get('/ajuda', fn() => view('geral.ajuda'))->name('ajuda');

    Route::get('/seguranca', [SegurancaController::class, 'showAlterarSenhaForm'])->name('seguranca');
    Route::post('/alterar-senha', [SegurancaController::class, 'alterarSenha'])->name('alterarSenha');
    Route::get('/configuracoes', [SegurancaController::class, 'configuracoes'])->name('configuracoes');
    Route::get('/perfil', [ConfiguracaoController::class, 'perfil'])->name('perfil');
    Route::post('/perfil/update', [ConfiguracaoController::class, 'atualizarPerfil'])->name('perfil.update');

    Route::get('/pacientes', function () {
        $pacientes = \App\Models\Paciente::orderBy('nomePaciente')->paginate(10);
        return view('geral.pacientes', compact('pacientes'));
    })->name('pacientes.index');

    Route::get('/cadastroPaciente', function () {
        return view('admin.cadastroPaciente');
    })->name('cadastroPaciente');

    Route::post('/paciente/store', [PacienteController::class, 'store'])->name('paciente.store');



    // CRUD MÉDICOS
    Route::get('/manutencaoMedicos', [MedicoController::class, 'index'])->name('manutencaoMedicos');
    Route::get('/cadastroMedico', [MedicoController::class, 'create'])->name('medicos.create');
    Route::post('/medicos/register', [MedicoController::class, 'store'])->name('medicos.register');
    Route::get('/medicos/{id}/editar', [MedicoController::class, 'editar'])->name('medicos.editar');
    Route::put('/medicos/{id}', [MedicoController::class, 'update'])->name('medicos.update');
    Route::get('/medicos/{id}/excluir', [MedicoController::class, 'confirmarExclusao'])->name('medicos.confirmarExclusao');
    Route::delete('/medicos/{id}', [MedicoController::class, 'excluir'])->name('medicos.excluir');
    Route::post('/medicos/{id}/toggle-status', [MedicoController::class, 'toggleStatus'])->name('medicos.toggleStatus');

    // CRUD ENFERMEIROS
    Route::get('/manutencaoEnfermeiro', [EnfermeiroController::class, 'index'])->name('manutencaoEnfermeiro');
    Route::get('/cadastroEnfermeiro', [EnfermeiroController::class, 'create'])->name('enfermeiro.create');
    Route::post('/enfermeiro/register', [EnfermeiroController::class, 'store'])->name('enfermeiro.register');
    Route::get('/enfermeiro/{id}/editar', [EnfermeiroController::class, 'editar'])->name('enfermeiro.editar');
    Route::put('/enfermeiro/{id}', [EnfermeiroController::class, 'update'])->name('enfermeiro.update');
    Route::get('/enfermeiro/{id}/excluir', [EnfermeiroController::class, 'confirmarExclusao'])->name('enfermeiro.confirmarExclusao');
    Route::delete('/enfermeiro/{id}', [EnfermeiroController::class, 'excluir'])->name('enfermeiro.excluir');
    Route::post('/enfermeiro/{id}/toggle-status', [EnfermeiroController::class, 'toggleStatus'])->name('enfermeiro.toggleStatus');
});
