// ==========================================
// 1. CATEGORÍA: ABOUT (about.php)
// ==========================================
gjsEditor.BlockManager.add('about-one', {
    label: 'About One',
    category: 'About',
    attributes: { class: 'fa fa-info-circle' },
    content: `<section class="about-area about-one">
    <div class="container">
    <div class="row">
    <div class="col-lg-12">
    <div class="about-title text-center">
    <h2 class="title fw-bold">Why Choose Us</h2>
    </div>
    </div>
    </div>
    <div class="row justify-content-center">
    <div class="col-md-4 col-sm-8">
    <div class="single-about-items">
    <div class="items-icon"><i class="lni lni-bullhorn"></i></div>
    <div class="items-content">
    <h4 class="items-title">Digital Marketing</h4>
    <p class="text">Short description for the ones who look for something new</p>
    </div>
    </div>
    </div>
    <div class="col-md-4 col-sm-8">
    <div class="single-about-items">
    <div class="items-icon"><i class="lni lni-investment"></i></div>
    <div class="items-content">
    <h4 class="items-title">Consulting Services</h4>
    <p class="text">Short description for the ones who look for something new</p>
    </div>
    </div>
    </div>
    <div class="col-md-4 col-sm-8">
    <div class="single-about-items">
    <div class="items-icon"><i class="lni lni-handshake"></i></div>
    <div class="items-content">
    <h4 class="items-title">Business Solutions</h4>
    <p class="text">Short description for the ones who look for something new</p>
    </div>
    </div>
    </div>
    </div>
    </div>
    </section>`
});

gjsEditor.BlockManager.add('about-two', {
    label: 'About Two',
    category: 'About',
    attributes: { class: 'fa fa-info-circle' },
    content: `<section class="about-area about-two">
    <div class="container">
    <div class="row">
    <div class="col-lg-12">
    <div class="about-title text-center">
    <div class="section-title">
    <h2 class="fw-bold">Our Key Features</h2>
    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
    </div>
    </div>
    </div>
    </div>
    <div class="row justify-content-center">
    <div class="col-xl-5 col-lg-6 col-md-8 col-sm-11">
    <div class="single-features-one-items text-center">
    <div class="features-image">
    <img src="https://placehold.co/600x400?text=Imagen" alt="image" />
    </div>
    <div class="features-content">
    <h3 class="features-title">Social Media Marketin</h3>
    <p class="text">Lorem ipsum dolor sit amet, consectetur adipisicing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
    </div>
    </div>
    </div>
    <div class="col-xl-5 col-lg-6 col-md-8 col-sm-11">
    <div class="single-features-one-items text-center">
    <div class="features-image">
    <img src="https://placehold.co/600x400?text=Imagen" alt="image" />
    </div>
    <div class="features-content">
    <h3 class="features-title">Dedicated Team</h3>
    <p class="text">Lorem ipsum dolor sit amet, consectetur adipisicing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
    </div>
    </div>
    </div>
    </div>
    </div>
    </section>`
});

// ==========================================
// 2. CATEGORÍA: ACCORDIONS (accordion.php)
// ==========================================
gjsEditor.BlockManager.add('accordion-standard', {
    label: 'Standard Accordion',
    category: 'Accordions',
    attributes: { class: 'fa fa-list-ul' },
    content: `<div class="accordion" id="accordionExample">
    <div class="accordion-item">
    <h2 class="accordion-header" id="headingOne">
    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Accordion Item #1</button>
    </h2>
    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
    <div class="accordion-body"><strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the appropriate classes.</div>
    </div>
    </div>
    <div class="accordion-item">
    <h2 class="accordion-header" id="headingTwo">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Accordion Item #2</button>
    </h2>
    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
    <div class="accordion-body"><strong>This is the second item's accordion body.</strong> It is hidden by default.</div>
    </div>
    </div>
    </div>`
});

gjsEditor.BlockManager.add('accordion-flush', {
    label: 'Flush Accordion',
    category: 'Accordions',
    attributes: { class: 'fa fa-list-ul' },
    content: `<div class="accordion accordion-flush" id="accordionFlushExample">
    <div class="accordion-item">
    <h2 class="accordion-header" id="flush-headingOne">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">Accordion Item #1</button>
    </h2>
    <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
    <div class="accordion-body">Placeholder content for this accordion, which is intended to demonstrate the <code>.accordion-flush</code> class.</div>
    </div>
    </div>
    </div>`
});

// ==========================================
// 3. CATEGORÍA: ACTIONS / CTA (actions.php)
// ==========================================
gjsEditor.BlockManager.add('action-serenity', {
    label: 'Action Serenity',
    category: 'Actions',
    attributes: { class: 'fa fa-bullhorn' },
    content: `<div class="py-5 text-center">
    <div class="container">
    <div class="row">
    <div class="col-md-8 mx-auto">
    <p class="mb-3">"A wonderful serenity has taken possession of my entire soul, like these sweet mornings of spring which I enjoy with my whole heart."</p>
    <a class="btn btn-primary" href="#">Act now!</a>
    </div>
    </div>
    </div>
    </div>`
});

gjsEditor.BlockManager.add('action-bold', {
    label: 'Action Bold',
    category: 'Actions',
    attributes: { class: 'fa fa-bullhorn' },
    content: `<div class="py-5 text-center">
    <div class="container">
    <div class="row">
    <div class="mx-auto col-md-5">
    <p class="mb-3"><b>When, while the lovely valley teems with vapour around me, and the meridian sun strikes the upper surface of the impenetrable foliage of my trees.</b></p>
    <a class="btn btn-primary" href="#">Do something</a>
    </div>
    </div>
    </div>
    </div>`
});

gjsEditor.BlockManager.add('action-friends', {
    label: 'Social Friends',
    category: 'Actions',
    attributes: { class: 'fa fa-share-alt' },
    content: `<div class="py-5 text-center">
    <div class="container">
    <div class="row">
    <div class="col-md-12">
    <h1 class="mb-3">We <i class="fa fa-fw fa-heart d-inline text-danger"></i> new friends</h1>
    </div>
    </div>
    <div class="row">
    <div class="col-12">
    <a href="#" class="text-muted"><i class="fa fa-fw fa-facebook fa-2x mx-3"></i></a>
    <a href="#" class="text-muted"><i class="fa fa-fw fa-twitter fa-2x mx-3"></i></a>
    <a href="#" class="text-muted"><i class="fa fa-fw fa-2x fa-google-plus mx-3"></i></a>
    <a href="#" class="text-muted"><i class="fa fa-fw fa-instagram fa-2x mx-3"></i></a>
    </div>
    </div>
    </div>
    </div>`
});

gjsEditor.BlockManager.add('action-split', {
    label: 'Split Action',
    category: 'Actions',
    attributes: { class: 'fa fa-columns' },
    content: `<div class="py-5">
    <div class="container">
    <div class="row">
    <div class="col-md-12">
    <h1 class="text-center">Do not wait. Act</h1>
    <p class="mb-4 text-center lead">I am alone, and feel the charm of existence in this spot.</p>
    <div class="row">
    <div class="p-3 col-md-8">
    <h3>Main feature here</h3>
    <p class="mb-3">I throw myself down among the tall grass by the trickling stream; and, as I lie close to the earth, a thousand unknown plants are noticed by me.</p>
    <a class="btn btn-primary mb-3" href="#">Call to action</a>
    </div>
    <div class="col-md-4 col-lg-3 offset-lg-1">
    <img class="img-fluid d-block" src="https://placehold.co/600x400?text=Imagen">
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>`
});

// ==========================================
// 4. CATEGORÍA: BUTTONS (buttons.php)
// ==========================================
gjsEditor.BlockManager.add('btn-primary', {
    label: 'Primary Button',
    category: 'Buttons',
    attributes: { class: 'fa fa-square' },
    content: `<a class="btn btn-primary" href="#">Button</a>`
});

gjsEditor.BlockManager.add('btn-outline-primary', {
    label: 'Outline Primary',
    category: 'Buttons',
    attributes: { class: 'fa fa-square-o' },
    content: `<a class="btn btn-outline-primary" href="#">Outline</a>`
});

gjsEditor.BlockManager.add('btn-group', {
    label: 'Button Group',
    category: 'Buttons',
    attributes: { class: 'fa fa-th' },
    content: `<div class="btn-group">
    <a href="#" class="btn btn-primary">Btn 1</a>
    <a href="#" class="btn btn-primary">Btn 2</a>
    <a href="#" class="btn btn-primary">Btn 3</a>
    </div>`
});

gjsEditor.BlockManager.add('btn-dropdown', {
    label: 'Dropdown Button',
    category: 'Buttons',
    attributes: { class: 'fa fa-caret-square-o-down' },
    content: `<div class="btn-group">
    <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"> Dropdown </button>
    <div class="dropdown-menu">
    <a class="dropdown-item" href="#">Action</a>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item" href="#">Separated link</a>
    </div>
    </div>`
});

gjsEditor.BlockManager.add('btn-social-twitter', {
    label: 'Twitter Button',
    category: 'Buttons',
    attributes: { class: 'fa fa-twitter' },
    content: `<a class="btn text-white" href="#" style="background:#55acee" target="_blank"> <i class="fa fa-twitter fa-fw fa-1x py-1"></i> Tweet </a>`
});

gjsEditor.BlockManager.add('btn-social-facebook', {
    label: 'Facebook Button',
    category: 'Buttons',
    attributes: { class: 'fa fa-facebook' },
    content: `<a class="btn text-white" href="#" style="background:#3b5998" target="_blank"> <i class="fa fa-facebook fa-fw fa-1x py-1"></i> Share </a>`
});

// ==========================================
// 5. CATEGORÍA: ARTICLES / CONTENT (articles.php)
// ==========================================
gjsEditor.BlockManager.add('article-header-center', {
    label: 'Header Centered',
    category: 'Articles',
    attributes: { class: 'fa fa-header' },
    content: `<div class="py-5 text-center">
    <div class="container">
    <div class="row">
    <div class="mx-auto col-md-8">
    <h1 class="mb-3">I lie close to the earth, a thousand unknown plants are noticed by me.</h1>
    <p class="lead">When, while the lovely valley teems with vapour around me.</p>
    </div>
    </div>
    </div>
    </div>`
});

gjsEditor.BlockManager.add('article-split-image', {
    label: 'Split with Image',
    category: 'Articles',
    attributes: { class: 'fa fa-image' },
    content: `<div class="py-5">
    <div class="container">
    <div class="row">
    <div class="px-lg-5 d-flex flex-column justify-content-center col-lg-6">
    <h1>O my friend</h1>
    <p class="mb-3 lead">I hear the buzz of the little world among the stalks, and grow familiar with the countless indescribable forms of the insects and flies</p>
    </div>
    <div class="col-lg-6">
    <img class="img-fluid d-block" src="https://placehold.co/600x400?text=Imagen">
    </div>
    </div>
    </div>
    </div>`
});

gjsEditor.BlockManager.add('article-features-list', {
    label: 'Features List',
    category: 'Articles',
    attributes: { class: 'fa fa-list' },
    content: `<div class="py-5">
    <div class="container">
    <div class="row">
    <div class="mx-auto col-lg-6 col-md-8">
    <ul>
    <li class="mb-2"><b>Absorbed</b> - in the exquisite sense of mere tranquil existence, that I neglect my talents.</li>
    <li class="mb-2"><b>Moment</b> - yet I feel that I never was a greater artist than now. When, while the lovely valley teems with vapour around me.</li>
    <li class="mb-2"><b>Sun</b> - strikes the upper surface of the impenetrable foliage of my trees.</li>
    </ul>
    </div>
    </div>
    </div>
    </div>`
});

gjsEditor.BlockManager.add('article-quote', {
    label: 'Blockquote Style',
    category: 'Articles',
    attributes: { class: 'fa fa-quote-left' },
    content: `<div class="py-5">
    <div class="container">
    <div class="row">
    <div class="col-md-9 ml-auto border-left">
    <p class="mb-0">As I lie close to the earth, a thousand unknown plants are noticed by me: when I hear the buzz of the little world among the stalks, and grow familiar with the countless indescribable forms of the insects and flies, then I feel the presence of the Almighty.</p>
    </div>
    </div>
    </div>
    </div>`
});
