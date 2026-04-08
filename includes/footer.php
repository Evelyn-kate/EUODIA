</main>

<footer>
  <div class="footer-main">

    <div class="footer-col brand">
      <h3>EUODIA</h3>
      <p class="tagline">Peace Scents</p>
      <p class="about">Your destination for luxury decant perfumes. Authentic fragrances, crafted for elegance and affordability.</p>
      <div class="social-icons">
        <a href="#" title="Instagram"><i>📷</i></a>
        <a href="#" title="Facebook"><i>📘</i></a>
        <a href="#" title="WhatsApp"><i>💬</i></a>
        <a href="#" title="Twitter"><i>🐦</i></a>
      </div>
    </div>

    <div class="footer-col">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="/euodia/index.php">Home</a></li>
        <li><a href="/euodia/search.php">Shop</a></li>
        <li><a href="/euodia/cart.php">Cart</a></li>
        <li><a href="/euodia/auth/login.php">My Account</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Categories</h4>
      <ul>
        <li><a href="/euodia/search.php?cat=1">Signature Collection</a></li>
        <li><a href="/euodia/search.php?cat=2">Vintage Elegance</a></li>
        <li><a href="/euodia/search.php?cat=3">Amber Essence</a></li>
        <li><a href="/euodia/search.php?cat=4">Travel Decants</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Contact Us</h4>
      <ul class="contact-info">
        <li>📍 Buea, Cameroon</li>
        <li>📞 +237 6 78 31 0979</li>
        <li>✉️ mbemeva@gmail.com </li>
      </ul>
      <div class="payment-methods">
        <span>We Accept:</span>
        <div class="methods">
          <span class="method">MTN MoMo</span>
          <span class="method">Orange Money</span>
        </div>
      </div>
    </div>

  </div>

  <div class="footer-bottom">
    <p>© <?php echo date('Y'); ?> Euodia Peace Scents — All rights reserved.</p>
    <div class="footer-links">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Service</a>
      <a href="#">Shipping Info</a>
    </div>
  </div>
</footer>

<style>
footer {
  background: linear-gradient(180deg, #0a0a0a, #000);
  color: #ccc;
  padding: 0;
  margin-top: 60px;
  border-top: 1px solid #222;
}

.footer-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 50px 20px;
  display: flex;
  flex-wrap: wrap;
  gap: 40px;
  justify-content: space-between;
}

.footer-col {
  flex: 1;
  min-width: 200px;
}

.footer-col.brand {
  max-width: 280px;
}

.footer-col h3 {
  color: #d4af37;
  font-size: 1.8em;
  letter-spacing: 3px;
  margin-bottom: 5px;
}

.footer-col .tagline {
  color: #888;
  font-size: 0.95em;
  margin-bottom: 15px;
  letter-spacing: 1px;
}

.footer-col .about {
  color: #777;
  font-size: 0.9em;
  line-height: 1.6;
  margin-bottom: 20px;
}

.footer-col h4 {
  color: #d4af37;
  font-size: 1.1em;
  margin-bottom: 20px;
  letter-spacing: 1px;
  border-bottom: 1px solid #333;
  padding-bottom: 10px;
}

.footer-col ul {
  list-style: none;
}

.footer-col ul li {
  margin-bottom: 12px;
}

.footer-col ul li a {
  color: #aaa;
  text-decoration: none;
  font-size: 0.95em;
  transition: color 0.3s, padding-left 0.3s;
}

.footer-col ul li a:hover {
  color: #d4af37;
  padding-left: 5px;
}

.contact-info li {
  color: #999;
  font-size: 0.9em;
}

.social-icons {
  display: flex;
  gap: 15px;
}

.social-icons a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: #1a1a1a;
  border: 1px solid #333;
  border-radius: 50%;
  color: #d4af37;
  font-size: 1.1em;
  text-decoration: none;
  transition: background 0.3s, transform 0.3s;
}

.social-icons a:hover {
  background: #d4af37;
  color: #000;
  transform: scale(1.1);
}

.payment-methods {
  margin-top: 20px;
}

.payment-methods span {
  color: #888;
  font-size: 0.85em;
}

.payment-methods .methods {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

.payment-methods .method {
  background: #1a1a1a;
  border: 1px solid #333;
  padding: 6px 12px;
  border-radius: 4px;
  font-size: 0.8em;
  color: #d4af37;
}

.footer-bottom {
  background: #000;
  padding: 20px;
  text-align: center;
  border-top: 1px solid #222;
}

.footer-bottom p {
  color: #555;
  font-size: 0.9em;
  margin-bottom: 10px;
}

.footer-links {
  display: flex;
  justify-content: center;
  gap: 25px;
}

.footer-links a {
  color: #666;
  text-decoration: none;
  font-size: 0.85em;
  transition: color 0.3s;
}

.footer-links a:hover {
  color: #d4af37;
}

@media (max-width: 768px) {
  .footer-main {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .footer-col {
    max-width: 100%;
  }

  .footer-col.brand {
    max-width: 100%;
  }

  .social-icons {
    justify-content: center;
  }

  .payment-methods .methods {
    justify-content: center;
  }

  .footer-links {
    flex-wrap: wrap;
    gap: 15px;
  }
}
</style>

</body>
</html>
