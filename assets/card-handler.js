/**
 * 大海导航 - Card Click Handler
 * 根据后台设置决定卡片跳转行为
 */
(function() {
    'use strict';

    // 等待 DOM 加载完成
    document.addEventListener('DOMContentLoaded', function() {
        // 存储设置
        var useDetailPage = window.wpConfig && window.wpConfig.useDetailPage === 'detail';

        // 更新设置
        if (typeof wpConfig !== 'undefined') {
            useDetailPage = wpConfig.useDetailPage === 'detail';
        }

        // 获取设置
        fetch('/wp-json/ai-navigator/v1/settings')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                useDetailPage = data.useDetailPage === 'detail';
                attachCardHandlers();
            })
            .catch(function() {
                attachCardHandlers();
            });

        function attachCardHandlers() {
            // 查找所有工具卡片 - 支持多种选择器
            var cards = document.querySelectorAll('[data-tool-id], .ai-tool-card');
            
            cards.forEach(function(card) {
                // 跳过已有处理器的卡片
                if (card.dataset.cardHandlerAttached) return;
                card.dataset.cardHandlerAttached = 'true';
                
                card.addEventListener('click', function(e) {
                    // 检查是否点击的是链接或其他需要阻止的元素
                    var link = card.querySelector('a');
                    if (link && link.contains(e.target)) {
                        return; // 让链接正常跳转
                    }
                    
                    e.preventDefault();
                    
                    // 获取工具 URL 和 ID
                    var toolUrl = card.getAttribute('data-tool-url') || 
                                  card.dataset.toolUrl;
                    
                    var toolSlug = card.getAttribute('data-tool-slug') ||
                                   card.dataset.toolSlug;
                    
                    if (useDetailPage && toolSlug) {
                        // 跳转到详情页
                        window.location.href = '/ai-tool/' + toolSlug + '/';
                    } else if (toolUrl) {
                        // 直接跳转到工具 URL
                        window.open(toolUrl, '_blank');
                    } else {
                        // 尝试从链接中获取
                        var cardLink = card.querySelector('a');
                        if (cardLink) {
                            var href = cardLink.href;
                            if (useDetailPage) {
                                window.location.href = href;
                            } else {
                                // 如果没有 tool_url 且设置为直接跳转，尝试提取 URL
                                // 或者直接跳转到详情页
                                window.location.href = href;
                            }
                        }
                    }
                });
            });
        }

        // MutationObserver 监听动态添加的卡片
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        attachCardHandlers();
                    }
                });
            });
            
            observer.observe(document.body, { childList: true, subtree: true });
        }
    });
})();
