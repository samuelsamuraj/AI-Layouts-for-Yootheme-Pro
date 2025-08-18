/**
 * AI Builder Extension for YOOtheme
 * 
 * This extension integrates AI Layout functionality directly into YOOtheme's customizer
 */

import { createApp } from 'vue'
import AILayoutPanel from './components/AILayoutPanel.vue'

const AIBuilderExtension = {
    install(app, options) {
        console.log('AI Builder Extension: Installing...');
        
        // Register the main component
        app.component('ai-layout-panel', AILayoutPanel)
        
        // Extend customizer with AI Builder functionality
        app.extend('customizer', {
            panels: {
                'ai-builder': {
                    title: 'AI Builder',
                    icon: 'layout',
                    component: 'ai-layout-panel'
                }
            }
        })
        
        // Add customizer route
        app.extend('customizer.routes', {
            'ai-builder': {
                path: '/ai-builder',
                component: 'ai-layout-panel'
            }
        })
        
        // Register customizer control type
        app.extend('customizer.controls', {
            'ai-builder-generator': {
                component: 'ai-layout-panel',
                props: {
                    type: 'ai-builder-generator'
                }
            }
        })
        
        console.log('AI Builder Extension: Installation complete');
    }
}

// Auto-install if running in browser
if (typeof window !== 'undefined' && window.YOOtheme) {
    console.log('AI Builder Extension: YOOtheme detected, installing...');
    window.YOOtheme.use(AIBuilderExtension);
} else {
    console.log('AI Builder Extension: YOOtheme not detected, waiting...');
}

export default AIBuilderExtension;
