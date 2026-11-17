import {Chart} from 'chart.js/auto';
// O Livewire 3 inclui Alpine.js e gerencia sua inicialização.
// Não iniciar outra instância de Alpine para evitar conflitos com Livewire.
window.Chart = Chart;
