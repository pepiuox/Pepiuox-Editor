
// ==========================================
// 1. HEADERS & NAVBARS (De blocks2.php y blocks3.php)
// ==========================================
gjsEditor.BlockManager.add('navbar-dark', {
    label: 'Navbar Dark',
    category: 'Headers',
    attributes: { class: 'fa fa-bars' },
    content: `<header class="bg-dark">
    <div class="container">
    <nav class="navbar navbar-expand-md navbar-dark">
    <a class="navbar-brand" href="#">
    <img src="https://placehold.co/600x400?text=Imagen" height="30" alt="Logo">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
    <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav mr-auto">
    <li class="nav-item active"><a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a></li>
    <li class="nav-item"><a class="nav-link" href="#">Features</a></li>
    <li class="nav-item"><a class="nav-link" href="#">Pricing</a></li>
    </ul>
    <a class="btn btn-outline-light ml-md-3" href="#">Button</a>
    </div>
    </nav>
    </div>
    </header>`
});

// ==========================================
// 2. FEATURES & SERVICES (De blocks1.php y blocks4.php)
// ==========================================
gjsEditor.BlockManager.add('features-grid', {
    label: 'Features Grid',
    category: 'Features',
    attributes: { class: 'fa fa-star' },
    content: `<section class="py-5">
    <div class="container">
    <div class="row text-center">
    <div class="col-12"><h1>Features</h1></div>
    </div>
    <div class="row text-center justify-content-center mt-5">
    <div class="col-12 col-sm-4 col-xl-3 m-md-auto">
    <img alt="image" class="fdb-icon" src="https://placehold.co/600x400?text=Imagen">
    <h3><strong>Feature 1</strong></h3>
    <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia</p>
    </div>
    <div class="col-12 col-sm-4 col-xl-3 m-auto pt-4 pt-sm-0">
    <img alt="image" class="fdb-icon" src="https://placehold.co/600x400?text=Imagen">
    <h3><strong>Feature 2</strong></h3>
    <p>Separated they live in Bookmarksgrove right at the coast of the Semantics, a large ocean.</p>
    </div>
    <div class="col-12 col-sm-4 col-xl-3 m-auto pt-4 pt-sm-0">
    <img alt="image" class="fdb-icon" src="https://placehold.co/600x400?text=Imagen">
    <h3><strong>Feature 3</strong></h3>
    <p>A small river named Duden flows by their place and supplies it with the necessary regelialia</p>
    </div>
    </div>
    </div>
    </section>`
});

// ==========================================
// 3. PRICING TABLES (De blocks3.php y blocks4.php)
// ==========================================
gjsEditor.BlockManager.add('pricing-3-cols', {
    label: 'Pricing 3 Columns',
    category: 'Pricing',
    attributes: { class: 'fa fa-tags' },
    content: `<section class="py-5">
    <div class="container">
    <div class="row text-center">
    <div class="col"><h1>Pricing</h1></div>
    </div>
    <div class="row mt-5 align-items-center">
    <div class="col-12 col-sm-10 col-md-8 m-auto col-lg-4 text-center">
    <div class="fdb-box p-4">
    <h2>Hobby</h2>
    <p class="lead">Far far away, behind the word mountains.</p>
    <p class="h1 mt-5 mb-5">$99</p>
    <p><a href="#" class="btn btn-dark">Buy Now</a></p>
    </div>
    </div>
    <div class="col-12 col-sm-10 col-md-8 m-auto col-lg-4 text-center pt-4 pt-lg-0">
    <div class="fdb-box px-4 pt-5">
    <h2>Advanced</h2>
    <p class="lead">Separated they live in Bookmarksgrove.</p>
    <p class="h1 mt-5 mb-5">$299</p>
    <p><a href="#" class="btn btn-secondary">Buy Now</a></p>
    </div>
    </div>
    <div class="col-12 col-sm-10 col-md-8 m-auto col-lg-4 text-center pt-4 pt-lg-0">
    <div class="fdb-box p-4">
    <h2>Business</h2>
    <p class="lead">Even the all-powerful Pointing has no control.</p>
    <p class="h1 mb-5 mt-5">$799</p>
    <p><a href="#" class="btn btn-dark">Buy Now</a></p>
    </div>
    </div>
    </div>
    </div>
    </section>`
});

// ==========================================
// 4. TEAM & TESTIMONIALS (De blocks3.php y blocks4.php)
// ==========================================
gjsEditor.BlockManager.add('team-cards', {
    label: 'Team Cards',
    category: 'Team',
    attributes: { class: 'fa fa-users' },
    content: `<section class="py-5 team-1">
    <div class="container">
    <div class="row text-center justify-content-center">
    <div class="col-8"><h1>Our Team</h1><p class="lead">Far far away, behind the word mountains.</p></div>
    </div>
    <div class="row-50"></div>
    <div class="row">
    <div class="col-sm-3 text-left">
    <div class="fdb-box p-0">
    <img alt="image" class="img-fluid rounded-0" src="https://placehold.co/600x400?text=Imagen">
    <div class="content p-3">
    <h3><strong>Sara Doe</strong></h3>
    <p>Founder</p>
    </div>
    </div>
    </div>
    <div class="col-sm-3 text-left">
    <div class="fdb-box p-0">
    <img alt="image" class="img-fluid rounded-0" src="https://placehold.co/600x400?text=Imagen">
    <div class="content p-3">
    <h3><strong>John Smith</strong></h3>
    <p>Co-founder</p>
    </div>
    </div>
    </div>
    <div class="col-sm-3 text-left">
    <div class="fdb-box p-0">
    <img alt="image" class="img-fluid rounded-0" src="https://placehold.co/600x400?text=Imagen">
    <div class="content p-3">
    <h3><strong>Jane Doe</strong></h3>
    <p>Designer</p>
    </div>
    </div>
    </div>
    <div class="col-sm-3 text-left">
    <div class="fdb-box p-0">
    <img alt="image" class="img-fluid rounded-0" src="https://placehold.co/600x400?text=Imagen">
    <div class="content p-3">
    <h3><strong>Mike Ross</strong></h3>
    <p>Developer</p>
    </div>
    </div>
    </div>
    </div>
    </div>
    </section>`
});

// ==========================================
// 5. CARDS & CONTENT (De blocks0.php y blocks1.php)
// ==========================================
gjsEditor.BlockManager.add('card-image-top', {
    label: 'Card with Image',
    category: 'Cards',
    attributes: { class: 'fa fa-id-card' },
    content: `<div class="card">
    <img class="card-img-top" src="https://placehold.co/600x400?text=Imagen" alt="Card image cap">
    <div class="card-body">
    <h4 class="card-title">Card title</h4>
    <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
    <a href="#" class="btn btn-primary">Go somewhere</a>
    </div>
    </div>`
});

// ==========================================
// 6. FORMS & CONTACT (De blocks0.php, blocks2.php y blocks4.php)
// ==========================================
gjsEditor.BlockManager.add('contact-form-map', {
    label: 'Contact Form + Map',
    category: 'Forms',
    attributes: { class: 'fa fa-envelope' },
    content: `<section class="py-5 pt-0">
    <div class="container-fluid p-0 pb-md-5">
    <iframe class="map" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2848.8444388087937!2d26.101253041406952!3d44.43635311654287!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40b1ff4770adb5b7%3A0x58147f39579fe6fa!2zR3J1cHVsIFN0YXR1YXIgIkPEg3J1yJthIEN1IFBhaWHIm2Ui!5e0!3m2!1sen!2sro!4v1507381157656" width="100%" height="300" frameborder="0" style="border:0" allowfullscreen=""></iframe>
    </div>
    <div class="container">
    <div class="row mt-5">
    <div class="col-12 col-md-6 col-lg-5">
    <h2>Contact Us</h2>
    <p class="lead">Far far away, behind the word mountains, there live the blind texts.</p>
    <p class="h3 mt-5"><strong>Email:</strong> <a href="#">hello@website.com</a></p>
    <p class="lead"><strong>Phone:</strong> <a href="#">+44 123 123 1232</a></p>
    </div>
    <div class="col-12 col-md-6 ml-auto pt-5 pt-md-0">
    <form>
    <div class="row">
    <div class="col"><input type="text" class="form-control" placeholder="First name"></div>
    <div class="col"><input type="text" class="form-control" placeholder="Last name"></div>
    </div>
    <div class="row mt-4"><div class="col"><input type="email" class="form-control" placeholder="Enter email"></div></div>
    <div class="row mt-4"><div class="col"><textarea class="form-control" name="message" rows="3" placeholder="How can we help?"></textarea></div></div>
    <div class="row mt-4"><div class="col"><button type="submit" class="btn btn-primary">Submit</button></div></div>
    </form>
    </div>
    </div>
    </div>
    </section>`
});

gjsEditor.BlockManager.add('newsletter-form', {
    label: 'Newsletter Subscribe',
    category: 'Forms',
    attributes: { class: 'fa fa-newspaper-o' },
    content: `<section class="py-5 bg-primary text-white text-center">
    <div class="container">
    <div class="row justify-content-center">
    <div class="col-md-8">
    <h2>Subscribe to Our Newsletter</h2>
    <p class="mb-4">Stay updated with our latest news and offers.</p>
    <form class="d-flex justify-content-center">
    <input type="email" class="form-control me-2" placeholder="Enter your email" style="max-width: 300px;">
    <button type="submit" class="btn btn-light">Subscribe</button>
    </form>
    </div>
    </div>
    </div>
    </section>`
});

// ==========================================
// 7. FOOTERS (De blocks0.php y blocks4.php)
// ==========================================
gjsEditor.BlockManager.add('footer-large', {
    label: 'Large Footer',
    category: 'Footers',
    attributes: { class: 'fa fa-level-down' },
    content: `<footer class="py-5 footer-large">
    <div class="container">
    <div class="row align-items-top text-center">
    <div class="col-12 col-sm-6 col-md-4 col-lg-3 text-sm-left">
    <h3><strong>Group 1</strong></h3>
    <nav class="nav flex-column">
    <a class="nav-link active" href="#">Home</a>
    <a class="nav-link" href="#">Features</a>
    <a class="nav-link" href="#">Pricing</a>
    <a class="nav-link" href="#">Team</a>
    </nav>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-lg-3 mt-5 mt-sm-0 text-sm-left">
    <h3><strong>Group 2</strong></h3>
    <nav class="nav flex-column">
    <a class="nav-link active" href="#">Privacy Policy</a>
    <a class="nav-link" href="#">Terms</a>
    <a class="nav-link" href="#">FAQ</a>
    <a class="nav-link" href="#">Support</a>
    </nav>
    </div>
    <div class="col-12 col-md-4 col-lg-3 text-md-left mt-5 mt-md-0">
    <h3><strong>About Us</strong></h3>
    <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
    </div>
    <div class="col-12 col-lg-2 ml-auto text-lg-left mt-4 mt-lg-0">
    <h3><strong>Follow Us</strong></h3>
    <p class="lead">
    <a href="#" class="mx-2"><i class="fab fa-twitter"></i></a>
    <a href="#" class="mx-2"><i class="fab fa-facebook"></i></a>
    <a href="#" class="mx-2"><i class="fab fa-instagram"></i></a>
    </p>
    </div>
    </div>
    <div class="row mt-3">
    <div class="col text-center">© 2024 Your Company. All Rights Reserved</div>
    </div>
    </div>
    </footer>`
});

// ==========================================
// 8. MEDIA & GALLERIES (De blocks2.php y blocks3.php)
// ==========================================
gjsEditor.BlockManager.add('image-gallery', {
    label: 'Image Gallery',
    category: 'Media',
    attributes: { class: 'fa fa-th' },
    content: `<div class="py-5">
    <div class="container">
    <div class="row">
    <div class="col-md-4 p-3"><img class="img-fluid d-block" src="https://placehold.co/600x400?text=Imagen"></div>
    <div class="col-md-4 p-3"><img class="img-fluid d-block" src="https://placehold.co/600x400?text=Imagen"></div>
    <div class="col-md-4 p-3"><img class="img-fluid d-block" src="https://placehold.co/600x400?text=Imagen"></div>
    </div>
    </div>
    </div>`
});

// ==========================================
// 9. UI COMPONENTS (De blocks0.php y blocks2.php)
// ==========================================
gjsEditor.BlockManager.add('ui-alert', {
    label: 'Alert Box',
    category: 'UI Components',
    attributes: { class: 'fa fa-exclamation-triangle' },
    content: `<div class="alert alert-primary" role="alert">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <h4 class="alert-heading">Well done!</h4>
    <p class="mb-0">Whenever you need to, be sure to use margin utilities to keep things nice and tidy.</p>
    </div>`
});

gjsEditor.BlockManager.add('ui-tabs', {
    label: 'Tabs',
    category: 'UI Components',
    attributes: { class: 'fa fa-folder' },
    content: `<div class="py-1">
    <ul class="nav nav-tabs">
    <li class="nav-item"><a href="#" class="active nav-link" data-toggle="tab" data-target="#tabone">Tab 1</a></li>
    <li class="nav-item"><a class="nav-link" href="#" data-toggle="tab" data-target="#tabtwo">Tab 2</a></li>
    <li class="nav-item"><a href="#" class="nav-link" data-toggle="tab" data-target="#tabthree">Tab 3</a></li>
    </ul>
    <div class="tab-content mt-2">
    <div class="tab-pane fade show active" id="tabone" role="tabpanel"><p>When I hear the buzz of the little world among the stalks.</p></div>
    <div class="tab-pane fade" id="tabtwo" role="tabpanel"><p>Who formed us in his own image, and the breath of that universal love.</p></div>
    <div class="tab-pane fade" id="tabthree" role="tabpanel"><p>In my soul and absorb its power, like the form of a beloved mistress.</p></div>
    </div>
    </div>`
});
