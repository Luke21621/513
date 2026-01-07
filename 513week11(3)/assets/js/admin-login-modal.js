// 管理员登录弹窗功能

// 创建登录弹窗HTML
function createAdminLoginModal() {
    const modalHTML = `
        <div id="adminLoginModal" class="admin-login-modal" style="display: none;">
            <div class="admin-login-modal-overlay" onclick="closeAdminLoginModal()"></div>
            <div class="admin-login-modal-content">
                <div class="admin-login-icon">🔧</div>
                <h1 class="admin-login-title">Admin Login</h1>
                <p class="admin-login-subtitle">Management System</p>
                
                <div id="adminLoginError" class="admin-login-error" style="display: none;"></div>
                
                <form id="adminLoginForm" onsubmit="handleAdminLogin(event)">
                    <div class="admin-login-form-group">
                        <label class="admin-login-label" for="admin_username">Username *</label>
                        <input 
                            class="admin-login-input" 
                            type="text" 
                            id="admin_username" 
                            name="username" 
                            placeholder="Enter username" 
                            required
                            autocomplete="username"
                        >
                    </div>
                    
                    <div class="admin-login-form-group">
                        <label class="admin-login-label" for="admin_password">Password *</label>
                        <input 
                            class="admin-login-input" 
                            type="password" 
                            id="admin_password" 
                            name="password" 
                            placeholder="Enter password" 
                            required
                            autocomplete="current-password"
                        >
                    </div>
                    
                    <button type="submit" class="admin-login-btn-login">Login</button>
                    <button type="button" class="admin-login-btn-cancel" onclick="closeAdminLoginModal()">Cancel</button>
                </form>
                
                <div class="admin-login-default-credentials">
                    <strong>Default:</strong> user: admin, pass: admin123
                </div>
            </div>
        </div>
    `;
    
    // 添加样式
    const styleHTML = `
        <style>
            .admin-login-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .admin-login-modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
            }
            
            .admin-login-modal-content {
                position: relative;
                background: #ffffff;
                border-radius: 16px;
                padding: 2.5rem;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                width: 100%;
                max-width: 420px;
                margin: 2rem;
                z-index: 10001;
            }
            
            .admin-login-icon {
                text-align: center;
                font-size: 3rem;
                color: #9aa3a6;
                margin-bottom: 1rem;
            }
            
            .admin-login-title {
                text-align: center;
                font-size: 1.75rem;
                font-weight: 700;
                color: #2d2d2d;
                margin: 0 0 0.5rem 0;
            }
            
            .admin-login-subtitle {
                text-align: center;
                font-size: 0.95rem;
                color: #9aa3a6;
                margin: 0 0 2rem 0;
            }
            
            .admin-login-form-group {
                margin-bottom: 1.25rem;
            }
            
            .admin-login-label {
                display: block;
                margin-bottom: 0.5rem;
                color: #2d2d2d;
                font-weight: 500;
                font-size: 0.9rem;
            }
            
            .admin-login-input {
                width: 100%;
                padding: 0.75rem 1rem;
                border-radius: 8px;
                border: 1px solid #e3e6eb;
                background: #ffffff;
                color: #2d2d2d;
                font-size: 1rem;
                outline: none;
                transition: border-color 0.2s, box-shadow 0.2s;
                box-sizing: border-box;
            }
            
            .admin-login-input:focus {
                border-color: #4a90e2;
                box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            }
            
            .admin-login-btn-login {
                width: 100%;
                padding: 0.75rem 1rem;
                background: #4a90e2;
                color: #ffffff;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
                margin-bottom: 0.75rem;
            }
            
            .admin-login-btn-login:hover {
                background: #357abd;
            }
            
            .admin-login-btn-cancel {
                width: 100%;
                padding: 0.75rem 1rem;
                background: #6c757d;
                color: #ffffff;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
            }
            
            .admin-login-btn-cancel:hover {
                background: #5a6268;
            }
            
            .admin-login-error {
                background: #fff5f5;
                color: #e53e3e;
                padding: 0.75rem;
                border-radius: 8px;
                margin-bottom: 1rem;
                border: 1px solid #fed7d7;
                font-size: 0.9rem;
            }
            
            .admin-login-default-credentials {
                text-align: center;
                font-size: 0.85rem;
                color: #9aa3a6;
                margin-top: 1.5rem;
                padding-top: 1.5rem;
                border-top: 1px solid #e3e6eb;
            }
        </style>
    `;
    
    // 添加样式到head
    if (!document.getElementById('admin-login-modal-styles')) {
        const styleElement = document.createElement('div');
        styleElement.id = 'admin-login-modal-styles';
        styleElement.innerHTML = styleHTML;
        document.head.appendChild(styleElement);
    }
    
    // 添加弹窗到body
    if (!document.getElementById('adminLoginModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
}

// 显示登录弹窗
function showAdminLoginModal() {
    createAdminLoginModal();
    const modal = document.getElementById('adminLoginModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

// 关闭登录弹窗
function closeAdminLoginModal() {
    const modal = document.getElementById('adminLoginModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        // 清空表单和错误信息
        const form = document.getElementById('adminLoginForm');
        if (form) form.reset();
        const errorDiv = document.getElementById('adminLoginError');
        if (errorDiv) {
            errorDiv.style.display = 'none';
            errorDiv.textContent = '';
        }
    }
}

// 处理登录提交
async function handleAdminLogin(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('admin_login', '1');
    
    const errorDiv = document.getElementById('adminLoginError');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // 显示加载状态
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Logging in...';
    }
    
    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }
    
    try {
        const response = await fetch('admin_login_handler.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // 登录成功，跳转到管理页面
            window.location.href = data.redirect || 'admin.php';
        } else {
            // 显示错误信息
            if (errorDiv) {
                const errorMessages = Array.isArray(data.errors) ? data.errors : [data.errors || 'Login failed'];
                errorDiv.textContent = errorMessages.join(', ');
                errorDiv.style.display = 'block';
            }
            
            // 恢复按钮状态
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login';
            }
        }
    } catch (error) {
        console.error('Login error:', error);
        if (errorDiv) {
            errorDiv.textContent = 'An error occurred. Please try again.';
            errorDiv.style.display = 'block';
        }
        
        // 恢复按钮状态
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Login';
        }
    }
}

// 页面加载完成后初始化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', createAdminLoginModal);
} else {
    createAdminLoginModal();
}

