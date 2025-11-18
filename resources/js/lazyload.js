export default class LazyLoad {
    constructor(element, offset, fn, direction = 'down') {
        this.element = element;
        this.offset = offset;
        this.fn = fn;
        this.direction = direction;
    }

    watch() {
        const el = this.element;
        const offset = this.offset;
        const callback = this.fn;
        const direction = this.direction;
        const lazyload = this;

        $(el).on('scroll.lazyload', { element: el, offset, callback, direction, lazyload }, this.checkScrollPosition);
    }

    checkScrollPosition(event) {
        var $element = $(event.data.element);
        var callback = event.data.callback;
        var lazyload = event.data.lazyload;
        var direction = event.data.direction;
        if (direction === 'down') {
            var scrollTop = ($element[0] === window) ? $(window).scrollTop() : $element.scrollTop();
            var innerHeight = ($element[0] === window) ? $(window).height() : $element.innerHeight();
            var scrollHeight = ($element[0] === window) ? $(document).height() : $element[0].scrollHeight;

            if (scrollTop + innerHeight >= scrollHeight - event.data.offset) {
                callback();
            }
        } else if (direction === 'up') {
            var scrollTop = $element.scrollTop();
            var contentHeight = $element.get(0).scrollHeight - $element.innerHeight();
            if (Math.round(contentHeight) + (scrollTop) <= event.data.offset) {
                callback();
            }
        }

        $element.off('scroll.lazyload');
        setTimeout(function () {
            lazyload.watch();
        }, 500);
    }
}
