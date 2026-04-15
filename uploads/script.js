let cart = JSON.parse(localStorage.getItem('cart')) || [];
//function to update cart count
function updateCount() {
  const cartCountEl = document.getElementById('cart-count');
  if (cartCountEl) {
    cartCountEl.textContent = "Cart: " + cart.length;
  }
}

document.querySelectorAll('.add').forEach(btn => {
  btn.addEventListener('click', e => {
    const p = e.target.closest('.product');
    const item = {
      id: p.dataset.id,
      name: p.dataset.name,
      price: parseFloat(p.dataset.price)
    };
    cart.push(item);
    localStorage.setItem('cart', JSON.stringify(cart));
    document.cookie="cart="+JSON.stringify(cart);
    updateCount();
    window.location.href = 'cart.php';
  });
});

updateCount();

//function to add product to cart using fetch API

function addToCart(id){
  const qty = parseInt(document.getElementById('qty')?.value) || 1;
  
  // Get JWT token from cookie
  const getJWTToken = () => {
    const name = "jwt_token=";
    const decodedCookie = decodeURIComponent(document.cookie);
    const cookieArray = decodedCookie.split(';');
    for(let cookie of cookieArray) {
      cookie = cookie.trim();
      if (cookie.indexOf(name) === 0) {
        return cookie.substring(name.length);
      }
    }
    return null;
  };

  const token = getJWTToken();
  if (!token) {
    alert("Please login first");
    window.location.href = '../auth/login.php';
    return;
  }

  fetch("../api/products.php?id="+id, {
    headers: {
      'Authorization': 'Bearer ' + token
    }
  })
  .then(r=>r.json())
  .then(p=>{
     const item = {
       id: p.id,
       name: p.name,
       price: p.price * qty,
       quantity: qty,
       unitPrice: p.price
     };
     cart.push(item);
     localStorage.setItem("cart",JSON.stringify(cart));
     document.cookie="cart="+JSON.stringify(cart);
     window.location.href = 'cart.php';
  });
}
