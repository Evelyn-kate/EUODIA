# IP Whitelist Security Feature

## Overview
The IP Whitelist feature provides an additional layer of security for the Euodia admin panel by restricting access to only approved IP addresses. This is particularly useful for protecting sensitive admin operations.

## Setup & Installation

### 1. Database Setup
Run the setup script to create necessary tables:

```
http://localhost/EUODIA/admin/setup_ipwhitelist.php
```

This will create:
- `ip_whitelist` - Stores whitelisted IP addresses
- `ip_access_logs` - Logs unauthorized access attempts
- `settings` - Stores IP whitelist enabled/disabled status

### 2. Initial Configuration
1. Go to Admin Dashboard → Settings
2. Navigate to "IP Whitelist Management" section
3. Note your current IP address (displayed at the bottom)
4. Add your current IP to the whitelist before enabling the feature
5. Enable the "Enforce IP Whitelist" checkbox
6. Click "Update"

## Features

### Managing IP Addresses

#### Add IP Address
- Go to Settings → IP Whitelist Management
- Enter the IP address in CIDR notation or single IP (e.g., `192.168.1.100` or `10.0.0.0/24`)
- Optionally add a description (e.g., "Office Network", "Home IP")
- Click "Add to Whitelist"

#### Toggle IP Status
- Each IP can be toggled between "Active" and "Inactive"
- Inactive IPs are treated as if they're not whitelisted
- Click "Toggle" button next to the IP

#### Remove IP Address
- Click "Delete" button next to the IP
- Confirm the deletion

### Enable/Disable Whitelist
- Use the checkbox at the top of IP Whitelist Management section
- When **disabled**: All IPs can access the admin panel
- When **enabled**: Only whitelisted IPs can access the admin panel

## How It Works

### Access Flow
1. User attempts to access admin panel
2. System retrieves user's IP address (handles proxies via Cloudflare, X-Forwarded-For, etc.)
3. If whitelist is **enabled**:
   - Checks if IP is in whitelist and has "active" status
   - If not whitelisted: Returns 403 Forbidden error
   - If whitelisted: Allows access to continue
4. If whitelist is **disabled**: All authenticated users can access

### IP Detection
The system intelligently detects your IP address even when behind proxies:
- Cloudflare (`HTTP_CF_CONNECTING_IP`)
- X-Forwarded-For headers
- Standard REMOTE_ADDR

The detected IP is displayed at the bottom of the IP Whitelist Management section.

### Access Logging
All unauthorized access attempts are logged in the `ip_access_logs` table with:
- IP Address
- Attempted Page/Section
- User Agent
- Timestamp

## Implementation Details

### Files Modified/Created

**Class:**
- `includes/ipwhitelist.php` - Main IPWhitelist class with all methods

**Admin Pages (Protected):**
- `admin/dashboard.php`
- `admin/products.php`
- `admin/orders.php`
- `admin/users.php`
- `admin/categories.php`
- `admin/add_product.php`
- `admin/edit_product.php`
- `admin/settings.php`

**Setup:**
- `admin/setup_ipwhitelist.php` - Database initialization

**Management UI:**
- `admin/settings.php` - IP Whitelist Management section

### Class Methods

```php
// Static method to get client IP
IPWhitelist::getClientIP()

// Check if whitelist is enabled
isWhitelistEnabled()

// Check if specific IP is whitelisted
isIPWhitelisted($ip = null)

// Check access and log unauthorized attempts
checkAndLogAccess($ip = null, $page = '')

// Management methods
getWhitelistedIPs()
addIP($ip, $description = '')
removeIP($id)
toggleIPStatus($id)
setWhitelistEnabled($enabled)
updateIPDescription($id, $description)

// Access logging
getAccessStats($limit = 100)
```

## Security Best Practices

1. **Test Before Enabling**: Always test the whitelist with your IP added before enabling it
2. **Keep Localhost**: The setup script adds 127.0.0.1 (localhost) by default
3. **Multiple IPs**: Add all IPs you might connect from (office, home, mobile, etc.)
4. **Monitor Logs**: Regularly check `ip_access_logs` for suspicious access attempts
5. **CIDR Notation**: Use CIDR notation for network ranges (e.g., `192.168.0.0/24`)
6. **Regular Updates**: Review and update the whitelist regularly as your network changes

## Troubleshooting

### "Access Denied: Your IP address is not whitelisted"
- Your current IP is not in the whitelist
- Check the IP displayed in Settings → IP Whitelist Management
- Add it to the whitelist
- If whitelist is causing issues, disable it temporarily in the settings

### Finding Your Public IP
- If you're not sure of your IP, it's displayed in the settings page
- Or visit: https://whatismyipaddress.com

### Access Logs
To check who tried to access the admin panel:
```sql
SELECT * FROM ip_access_logs 
ORDER BY attempt_time DESC 
LIMIT 20;
```

## Database Schema

### ip_whitelist Table
```sql
CREATE TABLE ip_whitelist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

### ip_access_logs Table
```sql
CREATE TABLE ip_access_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(50) NOT NULL,
    page VARCHAR(255),
    user_agent TEXT,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempt_time)
)
```

### settings Table
```sql
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

## Future Enhancements

Potential improvements:
- CIDR range support with proper validation
- Geo-blocking capabilities
- Rate limiting for repeated failed attempts
- IP whitelist export/import functionality
- Automatic IP detection and addition
- Email notifications for unauthorized access attempts

## Support

For issues or questions about the IP Whitelist feature, refer to this documentation or check the access logs for detailed information about what's happening.
