const logo = document.getElementById('logoImg');
const header = document.getElementById('header');
const divisor = 1.5; // коэффициент уменьшения

// Получаем начальную ширину из инлайн-стиля или вычисленного значения
let originalWidth;

function initOriginalWidth() {
    const inlineWidth = logo.style.width;
    if (inlineWidth) {
        originalWidth = parseFloat(inlineWidth);
    } else {
        originalWidth = logo.clientWidth;
    }
}

// Инициализация при загрузке
initOriginalWidth();

// Обработчик скролла с оптимизацией
let ticking = false;

function updateHeader() {
    const scrollY = window.scrollY;

    if (scrollY > 50) {
        const newWidth = originalWidth / divisor;
        logo.style.width = newWidth + 'px';

        // Адаптивная высота хедера
        if (window.innerWidth <= 426) {
            header.style.height = '55px';
        } else {
            header.style.height = '60px';
        }
    } else {
        logo.style.width = originalWidth + 'px';

        // Возврат к исходной высоте
        if (window.innerWidth <= 426) {
            header.style.height = '70px';
        } else {
            header.style.height = '80px';
        }
    }

    ticking = false;
}

window.addEventListener('scroll', () => {
    if (!ticking) {
        window.requestAnimationFrame(updateHeader);
        ticking = true;
    }
});

// Пересчет при изменении размера окна
let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        initOriginalWidth();
        updateHeader();
    }, 250);
});

// === АДАПТИВНЫЕ ТАБЛИЦЫ ===
// Автоматическое оборачивание таблиц в скролл-контейнер
function wrapTables() {
    const tables = document.querySelectorAll('main table');

    tables.forEach(table => {
        // Пропускаем уже обёрнутые таблицы
        if (table.parentElement.classList.contains('table-wrapper')) {
            return;
        }

        // Создаём обёртку
        const wrapper = document.createElement('div');
        wrapper.className = 'table-wrapper';

        // Оборачиваем таблицу
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
}

// Запускаем при загрузке страницы
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', wrapTables);
} else {
    wrapTables();
}
