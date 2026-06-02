// store.js - Token management
class TokenManager {
    constructor() {
        this.accessToken = null;
        this.refreshToken = null;
        this.refreshTimer = null;
    }
    
    setTokens(accessToken, refreshToken) {
        this.accessToken = accessToken;
        this.refreshToken = refreshToken;
        
        // Store refresh token in HttpOnly cookie is better, but for simplicity
        // store in memory only, not localStorage (to prevent XSS)
        
        // Schedule token refresh before expiry (14 minutes, 1 minute before expiry)
        this.scheduleRefresh();
    }
    
    scheduleRefresh() {
        if (this.refreshTimer) clearTimeout(this.refreshTimer);
        // Refresh at 14 minutes (60 seconds before 15-minute expiry)
        this.refreshTimer = setTimeout(() => this.refresh(), 14 * 60 * 1000);
    }
    
    async refresh() {
        try {
            const response = await fetch('../api/refresh.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ refresh_token: this.refreshToken })
            });
            
            if (response.ok) {
                const data = await response.json();
                this.setTokens(data.access_token, data.refresh_token);
            } else {
                // Refresh failed - redirect to login
                this.clear();
                window.location.href = '../auth/login.php';
            }
        } catch (error) {
            console.error('Token refresh failed:', error);
        }
    }
    
    clear() {
        this.accessToken = null;
        this.refreshToken = null;
        if (this.refreshTimer) clearTimeout(this.refreshTimer);
    }
    
    getAuthHeader() {
        return this.accessToken ? `Bearer ${this.accessToken}` : null;
    }
}

// API Client
class APIClient {
    constructor(tokenManager) {
        this.tokenManager = tokenManager;
    }
    
    async request(url, options = {}) {
        const headers = options.headers || {};
        const authHeader = this.tokenManager.getAuthHeader();
        
        if (authHeader) {
            headers['Authorization'] = authHeader;
        }
        
        const response = await fetch(url, {
            ...options,
            headers
        });
        
        // If unauthorized, try to refresh token once
        if (response.status === 401) {
            await this.tokenManager.refresh();
            // Retry with new token
            const newAuthHeader = this.tokenManager.getAuthHeader();
            if (newAuthHeader) {
                headers['Authorization'] = newAuthHeader;
                return fetch(url, { ...options, headers });
            }
        }
        
        return response;
    }
}

// Usage example
const tokenManager = new TokenManager();
const api = new APIClient(tokenManager);

// After login
async function login(email, password) {
    const response = await fetch('../auth/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    
    if (response.ok) {
        const data = await response.json();
        tokenManager.setTokens(data.access_token, data.refresh_token);
    }
    return response;
}

// Make authenticated request
async function getProtectedData() {
    const response = await api.request('../api/protected.php');
    return response.json();
}