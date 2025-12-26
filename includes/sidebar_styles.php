<style>
    :root {
        --color-primary: #9155fd;
        --color-primary-light: #b389ff;
        --color-success: #56ca00;
        --color-warning: #ffb400;
        --color-danger: #ff4c51;
        --color-info: #16b1ff;
        --bg-primary: #f5f5f9;
        --bg-card: #ffffff;
        --text-primary: #4b465c;
        --text-secondary: #6f6b7d;
        --border-color: #dbdade;
        --shadow-1: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
        --shadow-2: 0 3px 6px rgba(0, 0, 0, 0.15), 0 2px 4px rgba(0, 0, 0, 0.12);
        --shadow-hover: 0 8px 16px rgba(145, 85, 253, 0.2);
        --spacing-2: 16px;
        --spacing-3: 24px;
        --radius-md: 10px;
        --radius-lg: 16px;
        --sidebar-width: 260px;
        --sidebar-collapsed-width: 80px;
        --topbar-height: 70px;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background-color: var(--bg-primary);
        color: var(--text-primary);
        line-height: 1.6;
        font-size: 15px;
        -webkit-font-smoothing: antialiased;
    }

    /* Page Layout */
    .page-wrapper {
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background: #312d4b;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1000;
        overflow-y: auto;
    }

    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar-brand {
        padding: 24px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
    }

    .brand-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .brand-text {
        flex: 1;
        opacity: 1;
        transition: opacity 0.3s;
    }

    .sidebar.collapsed .brand-text {
        opacity: 0;
        width: 0;
        overflow: hidden;
    }

    .brand-title {
        font-size: 18px;
        font-weight: 700;
        white-space: nowrap;
    }

    .brand-subtitle {
        font-size: 11px;
        opacity: 0.8;
        white-space: nowrap;
    }

    /* Navigation */
    .nav-menu {
        padding: 20px 12px;
    }

    .nav-section-title {
        font-size: 11px;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 12px;
        padding: 0 16px;
        transition: opacity 0.3s;
    }

    .sidebar.collapsed .nav-section-title {
        opacity: 0;
        height: 0;
        margin: 0;
        padding: 0;
    }

    .nav-item {
        margin-bottom: 4px;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s;
        position: relative;
    }

    .nav-link:hover {
        background: rgba(255, 255, 255, 0.08);
        color: white;
    }

    .nav-link.active {
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(145, 85, 253, 0.4);
    }

    .nav-icon {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .nav-text {
        flex: 1;
        white-space: nowrap;
        opacity: 1;
        transition: opacity 0.3s;
    }

    .sidebar.collapsed .nav-text {
        opacity: 0;
        width: 0;
        overflow: hidden;
    }

    .nav-badge {
        background: var(--color-danger);
        color: white;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
    }

    .sidebar.collapsed .nav-badge {
        position: absolute;
        top: 8px;
        right: 8px;
    }

    /* Main Content */
    .main-content {
        flex: 1;
        margin-left: var(--sidebar-width);
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .sidebar.collapsed ~ .main-content {
        margin-left: var(--sidebar-collapsed-width);
    }

    /* Topbar */
    .topbar {
        background: var(--bg-card);
        height: var(--topbar-height);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 var(--spacing-3);
        box-shadow: var(--shadow-1);
        position: sticky;
        top: 0;
        z-index: 900;
    }

    .topbar-left {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
    }

    .sidebar-toggle {
        background: none;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--text-primary);
        transition: all 0.2s;
    }

    .sidebar-toggle:hover {
        background: var(--bg-primary);
        color: var(--color-primary);
    }

    .topbar-search {
        display: flex;
        align-items: center;
        background: var(--bg-primary);
        border-radius: 8px;
        padding: 8px 16px;
        width: 300px;
    }

    .topbar-search input {
        border: none;
        background: none;
        outline: none;
        width: 100%;
        font-family: inherit;
        font-size: 14px;
        color: var(--text-primary);
    }

    .topbar-search i {
        color: var(--text-secondary);
        margin-right: 8px;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .topbar-btn {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-primary);
        position: relative;
        transition: all 0.2s;
    }

    .topbar-btn:hover {
        background: var(--bg-primary);
        color: var(--color-primary);
    }

    .topbar-btn .badge-dot {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 8px;
        height: 8px;
        background: var(--color-danger);
        border-radius: 50%;
        border: 2px solid var(--bg-card);
    }

    .user-menu {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .user-menu:hover {
        background: var(--bg-primary);
    }

    .user-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 14px;
    }

    .user-info {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-weight: 600;
        font-size: 14px;
        color: var(--text-primary);
    }

    .user-role {
        font-size: 12px;
        color: var(--text-secondary);
    }

    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: var(--spacing-3);
    }

    /* Mobile Overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.mobile-open {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 0;
        }

        .topbar-search {
            display: none;
        }

        .user-info {
            display: none;
        }

        .dashboard-container {
            padding: 16px;
        }
    }
</style>

<script>
    // Sidebar Toggle Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // Toggle sidebar on button click
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                // Mobile: slide sidebar in/out
                sidebar.classList.toggle('mobile-open');
                sidebarOverlay.classList.toggle('active');
            } else {
                // Desktop: collapse/expand sidebar
                sidebar.classList.toggle('collapsed');
                // Save state to localStorage
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            }
        });

        // Close sidebar when clicking overlay (mobile)
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
        });

        // Restore sidebar state from localStorage (desktop only)
        if (window.innerWidth > 768) {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
            }
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
            } else {
                sidebar.classList.remove('collapsed');
            }
        });
    });
</script>
