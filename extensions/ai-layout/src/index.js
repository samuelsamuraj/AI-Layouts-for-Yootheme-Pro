/**
 * AI Layout Extension for YOOtheme
 * 
 * This extension integrates AI Layout functionality directly into YOOtheme's customizer
 */

import { createApp } from 'vue'
import AILayoutPanel from './components/AILayoutPanel.vue'

const AILayoutExtension = {
    install(app, options) {
        // Register customizer panel
        app.component('ai-layout-panel', AILayoutPanel)
        
        // Extend customizer with AI Layout functionality
        app.extend('customizer', {
            panels: {
                'ai-layout': {
                    title: 'AI Layout',
                    icon: 'layout',
                    component: 'ai-layout-panel'
                }
            }
        })
        
        // Add customizer route
        app.extend('customizer.routes', {
            'ai-layout': {
                path: '/ai-layout',
                component: 'ai-layout-panel'
            }
        })
        
        // Register customizer control type
        app.extend('customizer.controls', {
            'ai-layout-generator': {
                component: 'ai-layout-panel',
                props: {
                    type: 'ai-layout-generator'
                }
            }
        })
    }
}

// Auto-install if running in browser
if (typeof window !== 'undefined' && window.YOOtheme) {
    window.YOOtheme.use(AILayoutExtension);
}

export default AILayoutExtension;
