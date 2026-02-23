/**
 * Hospital Queue Notification System
 * Handles browser notifications for token status updates
 */

const HospitalNotifications = {
  permission: null,
  tokenNumber: null,
  bookingId: null,
  pollInterval: null,
  
  /**
   * Initialize the notification system
   * @param {Object} options - Configuration options
   */
  init: function(options = {}) {
    this.tokenNumber = options.tokenNumber || null;
    this.bookingId = options.bookingId || null;
    
    // Check if browser supports notifications
    if (!('Notification' in window)) {
      console.log('This browser does not support notifications');
      return;
    }
    
    this.permission = Notification.permission;
    
    // Set up callbacks
    this.onStatusChange = options.onStatusChange || function() {};
    this.onCancellation = options.onCancellation || function() {};
    
    // Start polling for status changes if we have a token
    if (this.tokenNumber || this.bookingId) {
      this.startPolling();
    }
  },
  
  /**
   * Show permission prompt for notifications
   */
  showPermissionPrompt: function() {
    if (this.permission === 'default') {
      Notification.requestPermission().then(permission => {
        this.permission = permission;
        if (permission === 'granted') {
          this.showNotification('Notifications Enabled', 'You will receive updates about your token status.');
        }
      });
    }
  },
  
  /**
   * Show a browser notification
   * @param {string} title - Notification title
   * @param {string} body - Notification body text
   * @param {string} icon - Optional icon URL
   */
  showNotification: function(title, body, icon = '🏥') {
    if (this.permission !== 'granted') {
      console.log('Notifications not permitted');
      return;
    }
    
    try {
      const notification = new Notification(title, {
        body: body,
        icon: icon,
        badge: icon,
        vibrate: [200, 100, 200],
        requireInteraction: true
      });
      
      notification.onclick = function() {
        window.focus();
        notification.close();
      };
      
      // Auto-close after 10 seconds
      setTimeout(() => notification.close(), 10000);
    } catch (error) {
      console.error('Error showing notification:', error);
    }
  },
  
  /**
   * Start polling for token status changes
   */
  startPolling: function() {
    // Poll every 10 seconds
    this.pollInterval = setInterval(() => {
      this.checkTokenStatus();
    }, 10000);
    
    // Also check immediately
    this.checkTokenStatus();
  },
  
  /**
   * Stop polling
   */
  stopPolling: function() {
    if (this.pollInterval) {
      clearInterval(this.pollInterval);
      this.pollInterval = null;
    }
  },
  
  /**
   * Check the current token status from API
   */
  checkTokenStatus: async function() {
    try {
      const token = this.tokenNumber || this.bookingId;
      if (!token) return;
      
      const response = await fetch(`api/check_token_status.php?token=${encodeURIComponent(token)}&booking_id=${encodeURIComponent(this.bookingId || token)}`);
      const data = await response.json();
      
      if (data.success) {
        // Check for cancellation
        if (data.status === 'cancelled') {
          this.handleCancellation(data);
        } else if (data.status === 'consulting') {
          this.handleConsulting(data);
        }
        
        // Call status change callback
        if (typeof this.onStatusChange === 'function') {
          this.onStatusChange(data);
        }
      }
    } catch (error) {
      console.error('Error checking token status:', error);
    }
  },
  
  /**
   * Handle token cancellation
   */
  handleCancellation: function(data) {
    this.stopPolling();
    this.showNotification(
      '❌ Token Cancelled',
      `Your token ${data.token_number} has been cancelled ${data.reason || ''}. Please contact reception.`
    );
    
    if (typeof this.onCancellation === 'function') {
      this.onCancellation(data);
    }
  },
  
  /**
   * Handle when it's the patient's turn
   */
  handleConsulting: function(data) {
    this.showNotification(
      '🔔 Your Turn!',
      `Token ${data.token_number} is now being called. Please proceed to ${data.doctor_name || 'the consultation room'}.`
    );
  }
};

// Export for use in other scripts
if (typeof window !== 'undefined') {
  window.HospitalNotifications = HospitalNotifications;
}
