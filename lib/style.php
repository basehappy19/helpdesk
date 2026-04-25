<link rel="stylesheet" href="/style/global.css">

<link rel="icon" href="/style/favicon/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="/style/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/style/favicon/favicon-16x16.png">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<link rel="apple-touch-icon" sizes="180x180" href="/style/favicon/apple-touch-icon.png">

<link rel="mask-icon" href="/style/favicon/safari-pinned-tab.svg" color="#5bbad5">

<link rel="manifest" href="/style/favicon/site.webmanifest">

<meta name="msapplication-config" content="/style/favicon/browserconfig.xml">
<meta name="msapplication-TileColor" content="#2d89ef">
<meta name="theme-color" content="#ffffff">

<script>
    function showToast(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'hot-toast';

        let iconHtml = type === 'success' ?
            `<svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>` :
            `<svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>`;

        toast.innerHTML = `${iconHtml} <span>${message}</span>`;
        container.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('show'));

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const msg = sessionStorage.getItem('toast_msg');
        const type = sessionStorage.getItem('toast_type');

        if (msg) {
            showToast(msg, type || 'success');
            sessionStorage.removeItem('toast_msg');
            sessionStorage.removeItem('toast_type');
        }
    });
</script>