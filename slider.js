(function(){
    function createDots(wrapper, count, goto){
        var dots = wrapper.querySelector('.sis-dots');
        if (!dots) return;
        dots.innerHTML = '';
        for (var i=0;i<count;i++){
            var b = document.createElement('button');
            b.setAttribute('role','tab');
            b.setAttribute('aria-label','Go to slide ' + (i+1));
            b.className = 'sis-dot' + (i===0 ? ' active' : '');
            (function(index){
                b.addEventListener('click', function(){ goto(index); });
            })(i);
            dots.appendChild(b);
        }
    }

    function sliderInit(opts){
        var root = document.getElementById(opts.id);
        if (!root) return;
        var track = root.querySelector('.sis-track');
        var slides = Array.prototype.slice.call(root.querySelectorAll('.sis-slide'));
        var prevBtn = root.querySelector('.sis-prev');
        var nextBtn = root.querySelector('.sis-next');
        var state = { index:0, timer:null, animating:false };
        var settings = opts.settings || { autoplay:true, speed:3000, transition:'slide' };

        function applyLayout(){
            var h = getComputedStyle(root).getPropertyValue('--sis-height');
            root.style.height = h;
            if (settings.transition === 'slide'){
                track.style.transform = 'translateX(' + (-state.index*100) + '%)';
            } else {
                slides.forEach(function(s, i){
                    s.style.opacity = (i === state.index) ? '1' : '0';
                });
            }
            updateDots();
        }

        function goto(index){
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;
            state.index = index;
            applyLayout();
            restartAutoplay();
        }

        function next(){ goto(state.index + 1); }
        function prev(){ goto(state.index - 1); }

        function updateDots(){
            var dots = root.querySelectorAll('.sis-dot');
            for (var i=0;i<dots.length;i++){
                if (i === state.index) dots[i].classList.add('active');
                else dots[i].classList.remove('active');
            }
        }

        createDots(root, slides.length, goto);
        applyLayout();

        if (nextBtn) nextBtn.addEventListener('click', next);
        if (prevBtn) prevBtn.addEventListener('click', prev);

        function startAutoplay(){
            if (settings.autoplay && slides.length > 1){
                state.timer = setInterval(next, Math.max(100, settings.speed || 3000));
            }
        }
        function stopAutoplay(){ if (state.timer){ clearInterval(state.timer); state.timer = null; } }
        function restartAutoplay(){ stopAutoplay(); startAutoplay(); }

        root.addEventListener('mouseenter', stopAutoplay);
        root.addEventListener('mouseleave', startAutoplay);
        window.addEventListener('resize', applyLayout);

        startAutoplay();
    }

    function runQueue(){
        if (!window.SIS_QUEUES) return;
        while (window.SIS_QUEUES.length){
            var item = window.SIS_QUEUES.shift();
            sliderInit(item);
        }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive'){
        runQueue();
    } else {
        document.addEventListener('DOMContentLoaded', runQueue);
    }
})();
