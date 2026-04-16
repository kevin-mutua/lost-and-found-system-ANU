<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/lost_and_found');
}
?>
<!-- Footer -->
<footer class="footer" style="background-color: #1a1a1a; padding: 4rem 0 2rem;">
    <div class="container">
        <div class="row">
            <!-- Logo and Description -->
            <div class="col-lg-4 col-md-6 footer-section">
                <div class="footer-logo">
                    <img src="<?php echo BASE_URL; ?>/assets/images/anu-logo-footer.png" alt="ANU Logo" class="logo" style="width: auto; height: 50px; border-radius: 0;">
                    <h5 class="text-white mt-3">ANU Lost and Found</h5>
                </div>
                <p class="text-white-50 mb-3">Find your lost items or report found ones at the African Nazarene University Lost and Found system.</p>
                <div class="footer-contact-item">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span class="text-white-50">Magadi Rd, Nairobi</span>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 footer-section">
                <h5 class="text-white">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE_URL; ?>/index.php" class="text-white-50 text-decoration-none">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/search.php" class="text-white-50 text-decoration-none">Search Items</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/report.php" class="text-white-50 text-decoration-none">Report Item</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/profile.php" class="text-white-50 text-decoration-none">My Profile</a></li>
                    <li><a href="<?php echo (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/auth/login.php'; ?>" class="text-white-50 text-decoration-none">Admin Panel</a></li>
                    <li><a href="#" class="text-white-50 text-decoration-none" onclick="showItemClaimsInfo(event)">Item Claims</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="col-lg-3 col-md-6 footer-section">
                <h5 class="text-white">Services</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE_URL; ?>/search.php?check_item_type=lost" class="text-white-50 text-decoration-none">Lost Items</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/search.php?check_item_type=found" class="text-white-50 text-decoration-none">Found Items</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/report.php" class="text-white-50 text-decoration-none">Report Lost Item</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/report.php" class="text-white-50 text-decoration-none">Report Found Item</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6 footer-section">
                <h5 class="text-white">Contact Info</h5>
                <div class="footer-contact-item">
                    <i class="bi bi-telephone-fill"></i>
                    <a href="tel:+254703970520" class="text-white-50 text-decoration-none" style="transition: color 0.3s;">+254 703 970 520</a>
                </div>
                <div class="footer-contact-item">
                    <i class="bi bi-envelope-fill"></i>
                    <a href="mailto:reports@anu.ac.ke" class="text-white-50 text-decoration-none" style="transition: color 0.3s;">reports@anu.ac.ke</a>
                </div>
                <div class="footer-contact-item">
                    <i class="bi bi-clock-fill"></i>
                    <span class="text-white-50">Mon-Fri: 8:00 AM - 5:00 PM</span>
                </div>
                <div class="footer-contact-item">
                    <i class="bi bi-building"></i>
                    <span class="text-white-50">Masai Lodge Campus</span>
                </div>
            </div>
        </div>

        <!-- Bottom Footer -->
        <div class="footer-bottom" style="background-color: #0a0a0a; margin-top: 2rem; padding: 1.5rem 1rem; border-top: 1px solid #333;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-white-50">&copy; <?php echo date('Y'); ?> ANU Lost and Found. All rights reserved.</p>
                </div>
                <div class="col-md-6">
                    <div class="text-md-end">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal" class="text-white-50 text-decoration-none">Privacy Policy</a> |
                        <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-white-50 text-decoration-none">Terms of Service</a> |
                        <a href="#" data-bs-toggle="modal" data-bs-target="#contactModal" class="text-white-50 text-decoration-none">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="privacyModalLabel"><i class="bi bi-shield-lock"></i> Privacy Policy</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mt-3 mb-2">1. Introduction</h6>
                <p>ANU Lost and Found ("System") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information in accordance with ANU ICT policies, the Kenyan Data Protection Act, and ethical research guidelines.</p>

                <h6 class="mt-3 mb-2">2. Information We Collect</h6>
                <ul>
                    <li><strong>Account Information:</strong> Name, registration number, email address, phone number, and profile picture</li>
                    <li><strong>Item Information:</strong> Descriptions, categories, locations, dates, and photographs of lost or found items</li>
                    <li><strong>Claims Data:</strong> Information related to item claims, verification status, and communications</li>
                    <li><strong>System Usage:</strong> Log files, IP addresses, and usage patterns (for security and system improvement)</li>
                </ul>

                <h6 class="mt-3 mb-2">3. Data Security & Confidentiality</h6>
                <p>We implement industry-standard security measures to protect your personal information:</p>
                <ul>
                    <li><strong>Encryption:</strong> All data transmissions use HTTPS encryption</li>
                    <li><strong>Secure Passwords:</strong> Passwords are hashed using secure algorithms (bcrypt/SHA-256)</li>
                    <li><strong>Access Control:</strong> Role-based access ensures only authorized personnel view sensitive data</li>
                    <li><strong>Database Protection:</strong> Regular backups and access logging</li>
                </ul>

                <h6 class="mt-3 mb-2">4. How We Use Your Information</h6>
                <ul>
                    <li>Facilitate lost and found item matching and retrieval</li>
                    <li>Process and verify item claims</li>
                    <li>Send notifications about matched items or claim updates</li>
                    <li>Improve matching algorithms and system functionality</li>
                    <li>Comply with university safety and administrative requirements</li>
                </ul>

                <h6 class="mt-3 mb-2">5. Matching Algorithm Transparency</h6>
                <p>Our matching algorithms are transparent and documented to prevent false positives. Matches are based on:</p>
                <ul>
                    <li>Item description and category similarities</li>
                    <li>Location proximity</li>
                    <li>Time proximity (reported/found dates)</li>
                    <li>User-verified information</li>
                </ul>
                <p>All automated matches require manual verification by security officers before notification.</p>

                <h6 class="mt-3 mb-2">6. Fair Use Policy</h6>
                <p>The System must not be used to:</p>
                <ul>
                    <li>Profile, track, or unfairly target users</li>
                    <li>Harass or intimidate other users</li>
                    <li>Submit false or misleading item reports</li>
                    <li>Manipulate the matching system</li>
                </ul>

                <h6 class="mt-3 mb-2">7. User Consent</h6>
                <p>By creating an account and using this system, you consent to the collection and use of your information as outlined in this policy. You may withdraw consent by contacting us, which will result in account deactivation.</p>

                <h6 class="mt-3 mb-2">8. Data Retention</h6>
                <p>Your data is retained for the duration of your account and 90 days after deactivation for audit and compliance purposes, after which it is securely deleted.</p>

                <h6 class="mt-3 mb-2">9. Third-Party Access</h6>
                <p>We do not sell, trade, or share your personal information with third parties except where required by law or with your explicit consent.</p>

                <h6 class="mt-3 mb-2">10. Your Rights</h6>
                <p>You have the right to:</p>
                <ul>
                    <li>Access your personal data</li>
                    <li>Request correction of inaccurate information</li>
                    <li>Request deletion of your account and associated data</li>
                    <li>Complaint to relevant data protection authorities</li>
                </ul>

                <h6 class="mt-3 mb-2">11. Contact Us</h6>
                <p>For privacy concerns or data requests, please contact us through the Contact Us modal or email <strong>reports@anu.ac.ke</strong></p>

                <p class="mt-4 text-muted small">Last Updated: <?php echo date('F j, Y'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Terms of Service Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="termsModalLabel"><i class="bi bi-file-text"></i> Terms of Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mt-3 mb-2">1. Acceptance of Terms</h6>
                <p>By accessing and using the ANU Lost and Found System, you agree to be bound by these Terms of Service. If you do not agree to any part of these terms, please do not use the system.</p>

                <h6 class="mt-3 mb-2">2. System Purpose</h6>
                <p>The ANU Lost and Found System is designed to help ANU community members (students, staff, and faculty) report, search for, and recover lost or found items within the university campus.</p>

                <h6 class="mt-3 mb-2">3. User Accounts</h6>
                <ul>
                    <li>You must provide accurate and complete information during registration</li>
                    <li>You are responsible for maintaining the confidentiality of your account credentials</li>
                    <li>You are liable for all activities that occur under your account</li>
                    <li>You must be a member of the ANU community to create an account</li>
                </ul>

                <h6 class="mt-3 mb-2">4. Prohibited Activities</h6>
                <p>Users must NOT:</p>
                <ul>
                    <li>Submit false, misleading, or fraudulent item reports</li>
                    <li>Harass, threaten, or defame other users</li>
                    <li>Attempt to hack, exploit, or gain unauthorized access to the system</li>
                    <li>Use profanity, hate speech, or offensive language</li>
                    <li>Upload inappropriate, illicit, or malicious content</li>
                    <li>Attempt to manipulate matching algorithms</li>
                    <li>Share other users' personal information without consent</li>
                    <li>Use the system for commercial or non-university purposes</li>
                </ul>

                <h6 class="mt-3 mb-2">5. Item Reporting Guidelines</h6>
                <ul>
                    <li>Provide accurate, detailed descriptions of items</li>
                    <li>Include relevant categories, colors, and distinguishing features</li>
                    <li>Specify the location where the item was lost or found</li>
                    <li>Do not report items of illegal nature or restricted materials</li>
                    <li>Do not submit duplicate reports</li>
                </ul>

                <h6 class="mt-3 mb-2">6. Claim Process</h6>
                <ul>
                    <li>Claims are subject to verification by security officers</li>
                    <li>You must provide accurate information to substantiate your claim</li>
                    <li>Items can only be claimed by the original owner or authorized representative</li>
                    <li>False claims may result in account suspension and disciplinary action</li>
                    <li>The university assumes no liability for items not collected within 30 days</li>
                </ul>

                <h6 class="mt-3 mb-2">7. Intellectual Property</h6>
                <p>All content in the system (logos, designs, text, graphics) is the property of African Nazarene University. Users may not reproduce, distribute, or modify any system content without permission.</p>

                <h6 class="mt-3 mb-2">8. Limitation of Liability</h6>
                <p>The university is not liable for:</p>
                <ul>
                    <li>Loss or damage to items reported through the system</li>
                    <li>Incorrect or incomplete matching results</li>
                    <li>Unauthorized access to user accounts</li>
                    <li>System downtime or technical issues</li>
                    <li>Third-party actions or interference</li>
                </ul>

                <h6 class="mt-3 mb-2">9. Dispute Resolution</h6>
                <p>Disputes regarding items or claims should be reported to the Lost and Found department at <strong>reports@anu.ac.ke</strong>. Decisions by university staff are final and binding.</p>

                <h6 class="mt-3 mb-2">10. Compliance & Enforcement</h6>
                <ul>
                    <li>The system complies with ANU ICT policies and the Kenyan Data Protection Act</li>
                    <li>Violations of these terms may result in account suspension or termination</li>
                    <li>Serious violations may be reported to university authorities for disciplinary action</li>
                    <li>Users agree to cooperate with investigations</li>
                </ul>

                <h6 class="mt-3 mb-2">11. Modifications to Terms</h6>
                <p>The university reserves the right to modify these terms at any time. Continued use of the system constitutes acceptance of updated terms.</p>

                <h6 class="mt-3 mb-2">12. Governing Law</h6>
                <p>These terms are governed by the laws of Kenya and the policies of African Nazarene University.</p>

                <p class="mt-4 text-muted small">Last Updated: <?php echo date('F j, Y'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Contact Us Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="contactModalLabel"><i class="bi bi-envelope"></i> Contact Us</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6><i class="bi bi-telephone-fill text-success"></i> Phone</h6>
                        <p class="mb-0">+254 703 970 520</p>
                        <p class="text-muted small">Mon-Fri: 8:00 AM - 5:00 PM</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-envelope-fill text-success"></i> Email</h6>
                        <p class="mb-0"><a href="mailto:reports@anu.ac.ke" class="text-decoration-none">reports@anu.ac.ke</a></p>
                        <p class="text-muted small">Response within 24 hours</p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6><i class="bi bi-geo-alt-fill text-success"></i> Visit Us</h6>
                        <p class="mb-0">Masai Lodge Campus</p>
                        <p class="text-muted small">Magadi Rd, Nairobi</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-building text-success"></i> Department</h6>
                        <p class="mb-0">Lost and Found Office</p>
                        <p class="text-muted small">Student Services Building</p>
                    </div>
                </div>

                <hr>

                <h6 class="mb-3">Send us a Message</h6>
                <form id="contactForm">
                    <div class="mb-3">
                        <label for="contactName" class="form-label">Your Name</label>
                        <input type="text" class="form-control" id="contactName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="contactEmail" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="contactEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="contactSubject" class="form-label">Subject</label>
                        <select class="form-select" id="contactSubject" name="subject" required>
                            <option value="">Select a subject...</option>
                            <option value="item_inquiry">Item Inquiry</option>
                            <option value="claim_issue">Claim Issue</option>
                            <option value="technical_support">Technical Support</option>
                            <option value="privacy_concern">Privacy Concern</option>
                            <option value="report_misuse">Report Misuse</option>
                            <option value="general_feedback">General Feedback</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="contactMessage" class="form-label">Message</label>
                        <textarea class="form-control" id="contactMessage" name="message" rows="5" placeholder="Please provide details about your inquiry..." required></textarea>
                    </div>
                    <div class="alert alert-info alert-sm" role="alert">
                        <small><i class="bi bi-info-circle"></i> We'll respond to your message within 24 business hours.</small>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-send"></i> Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Hostel Selection Modal for Student Quarters -->
<div class="modal fade" id="hostelSelectionModal" tabindex="-1" aria-labelledby="hostelSelectionTitle" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hostelSelectionTitle">Select Student Hostel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Which hostel is this item in?</p>
                <div id="hostelSelectionList">
                    <!-- Hostel selection buttons will be populated here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Handle contact form submission
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const name = document.getElementById('contactName').value;
    const email = document.getElementById('contactEmail').value;
    const subject = document.getElementById('contactSubject').value;
    const message = document.getElementById('contactMessage').value;
    
    // Prepare form data
    const formData = new FormData();
    formData.append('name', name);
    formData.append('email', email);
    formData.append('subject', subject);
    formData.append('message', message);
    
    // Send via fetch
    fetch('<?php echo BASE_URL; ?>/actions/send_contact_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Message sent successfully! We\'ll get back to you soon.');
            document.getElementById('contactForm').reset();
            bootstrap.Modal.getInstance(document.getElementById('contactModal')).hide();
        } else {
            alert('Error sending message: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending message. Please try again or email us directly.');
    });
});

// Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('<?php echo BASE_URL; ?>/service-worker.js', {
            scope: '<?php echo BASE_URL; ?>/'
        })
        .then(function(registration) {
            console.log('Service Worker registered successfully:', registration);
            
            // Check for updates periodically
            setInterval(function() {
                registration.update();
            }, 60000); // Check every minute
        })
        .catch(function(error) {
            console.log('Service Worker registration failed:', error);
        });
    });
}

// Check for online/offline status
window.addEventListener('online', function() {
    console.log('App is online');
    // Show a notification to user
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('ANU Lost & Found', {
            body: 'You are back online!',
            icon: '<?php echo BASE_URL; ?>/assets/images/icon-192x192.png'
        });
    }
});

window.addEventListener('offline', function() {
    console.log('App is offline');
    // Show a notification to user
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('ANU Lost & Found', {
            body: 'You are offline. Using cached data.',
            icon: '<?php echo BASE_URL; ?>/assets/images/icon-192x192.png'
        });
    }
});

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// Show Item Claims Information
function showItemClaimsInfo(event) {
    event.preventDefault();
    
    const userRole = '<?php echo isset($_SESSION['user_role']) ? htmlspecialchars($_SESSION['user_role']) : 'guest'; ?>';
    
    if (userRole === 'admin') {
        Swal.fire({
            icon: 'info',
            title: 'Admin Claims Dashboard',
            html: 'As an admin, you can manage and review all item claims in the system.<br><br>Access your admin dashboard to process claims.',
            confirmButtonColor: '#ed1c24',
            confirmButtonText: 'Go to Admin Dashboard'
        }).then(result => {
            if (result.isConfirmed) {
                window.location.href = '<?php echo BASE_URL; ?>/admin/dashboard.php';
            }
        });
    } else {
        Swal.fire({
            icon: 'info',
            title: 'My Item Claims',
            html: '<strong>Track your claims and statistics:</strong><br><br>' +
                  '✓ View any items you\'ve reported<br>' +
                  '✓ Track recovery status<br>' +
                  '✓ View your achievement level<br>' +
                  '✓ See your contribution points<br><br>' +
                  '<em>Claims are processed by the Lost & Found office for verification.</em>',
            confirmButtonColor: '#ed1c24',
            confirmButtonText: 'View My Profile'
        }).then(result => {
            if (result.isConfirmed) {
                window.location.href = '<?php echo BASE_URL; ?>/profile.php';
            }
        });
    }
}
</script>
</body>
</html>
