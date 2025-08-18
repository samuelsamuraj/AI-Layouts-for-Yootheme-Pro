<template>
  <div class="ai-layout-panel">
    <div class="ai-layout-header">
      <h2>AI Builder</h2>
      <p>Generate, review, and compile AI-driven layouts</p>
    </div>

    <div class="ai-layout-content">
      <!-- Input Section -->
      <div class="ai-layout-section">
        <h3>Input</h3>
        <div class="ai-layout-form">
          <div class="form-group">
            <label>URL (optional)</label>
            <input 
              v-model="input.url" 
              type="url" 
              placeholder="https://example.com"
              class="form-control"
            />
          </div>
          
          <div class="form-group">
            <label>Description *</label>
            <textarea 
              v-model="input.text" 
              rows="4" 
              placeholder="Describe your desired layout..."
              class="form-control"
              required
            ></textarea>
          </div>
          
          <div class="form-group">
            <label>Title</label>
            <input 
              v-model="input.title" 
              type="text" 
              placeholder="Layout title"
              class="form-control"
            />
          </div>
          
          <div class="form-group">
            <label>Comments</label>
            <input 
              v-model="input.comment" 
              type="text" 
              placeholder="Design goals, tone, constraints"
              class="form-control"
            />
          </div>
          
          <div class="ai-layout-actions">
            <button 
              @click="generateLayout" 
              :disabled="isGenerating || !input.text"
              class="btn btn-primary"
            >
              {{ isGenerating ? 'Generating...' : 'Generate Layout' }}
            </button>
            
            <button 
              v-if="wireframe"
              @click="regenerateUnlocked" 
              :disabled="isRegenerating"
              class="btn btn-secondary"
            >
              {{ isRegenerating ? 'Regenerating...' : 'Regenerate Unlocked' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Results Section -->
      <div v-if="analysis || wireframe || layout" class="ai-layout-section">
        <h3>Results</h3>
        
        <!-- Analysis -->
        <div v-if="analysis" class="ai-layout-result">
          <h4>Analysis</h4>
          <pre class="ai-layout-code">{{ JSON.stringify(analysis, null, 2) }}</pre>
        </div>
        
        <!-- Wireframe -->
        <div v-if="wireframe" class="ai-layout-result">
          <h4>Wireframe (DSL)</h4>
          <pre class="ai-layout-code">{{ JSON.stringify(wireframe, null, 2) }}</pre>
        </div>
        
        <!-- Compiled Layout -->
        <div v-if="layout" class="ai-layout-result">
          <h4>YOOtheme JSON</h4>
          <pre class="ai-layout-code">{{ JSON.stringify(layout, null, 2) }}</pre>
          
          <div class="ai-layout-actions">
            <button @click="downloadLayout" class="btn btn-success">
              Download JSON
            </button>
            
            <button @click="applyToPage" class="btn btn-primary">
              Apply to Current Page
            </button>
            
            <button @click="saveToLibrary" class="btn btn-secondary">
              Save to Library
            </button>
          </div>
        </div>
      </div>

      <!-- Error Display -->
      <div v-if="error" class="ai-layout-error">
        <p>{{ error }}</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AILayoutPanel',
  
  data() {
    return {
      input: {
        url: '',
        text: '',
        title: '',
        comment: ''
      },
      analysis: null,
      wireframe: null,
      layout: null,
      isGenerating: false,
      isRegenerating: false,
      error: null
    }
  },
  
  methods: {
    async generateLayout() {
      if (!this.input.text) {
        this.error = 'Please provide a description';
        return;
      }
      
      this.isGenerating = true;
      this.error = null;
      
      try {
        const response = await fetch(`${window.AI_LAYOUT_EXTENSION.restUrl}/generate`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.AI_LAYOUT_EXTENSION.nonce
          },
          body: JSON.stringify({
            input: {
              url: this.input.url || undefined,
              text: this.input.text
            },
            title: this.input.title || 'Generated Layout',
            comment: this.input.comment || ''
          })
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${await response.text()}`);
        }
        
        const data = await response.json();
        this.analysis = data.analysis;
        this.wireframe = data.wireframe;
        this.layout = data.layout;
        
      } catch (error) {
        this.error = `Generation failed: ${error.message}`;
        console.error('AI Layout generation error:', error);
      } finally {
        this.isGenerating = false;
      }
    },
    
    async regenerateUnlocked() {
      if (!this.wireframe) return;
      
      this.isRegenerating = true;
      this.error = null;
      
      try {
        const response = await fetch(`${window.AI_LAYOUT_EXTENSION.restUrl}/regenerate`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.AI_LAYOUT_EXTENSION.nonce
          },
          body: JSON.stringify({
            wireframe: this.wireframe
          })
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${await response.text()}`);
        }
        
        const data = await response.json();
        this.wireframe = data.wireframe;
        this.layout = data.layout;
        
      } catch (error) {
        this.error = `Regeneration failed: ${error.message}`;
        console.error('AI Layout regeneration error:', error);
      } finally {
        this.isRegenerating = false;
      }
    },
    
    async downloadLayout() {
      if (!this.layout) return;
      
      try {
        const response = await fetch(`${window.AI_LAYOUT_EXTENSION.restUrl}/download`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.AI_LAYOUT_EXTENSION.nonce
          },
          body: JSON.stringify({
            layout: this.layout
          })
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${await response.text()}`);
        }
        
        const data = await response.json();
        window.open(data.url, '_blank');
        
      } catch (error) {
        this.error = `Download failed: ${error.message}`;
        console.error('AI Layout download error:', error);
      }
    },
    
    async applyToPage() {
      if (!this.layout) return;
      
      try {
        const response = await fetch(`${window.AI_LAYOUT_EXTENSION.restUrl}/apply`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.AI_LAYOUT_EXTENSION.nonce
          },
          body: JSON.stringify({
            post_id: this.getCurrentPostId(),
            layout: this.layout
          })
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${await response.text()}`);
        }
        
        this.error = null;
        alert('Layout applied successfully!');
        
      } catch (error) {
        this.error = `Apply failed: ${error.message}`;
        console.error('AI Layout apply error:', error);
      }
    },
    
    async saveToLibrary() {
      if (!this.layout) return;
      
      try {
        const response = await fetch(`${window.AI_LAYOUT_EXTENSION.restUrl}/save-to-library`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.AI_LAYOUT_EXTENSION.nonce
          },
          body: JSON.stringify({
            layout: this.layout
          })
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${await response.text()}`);
        }
        
        const data = await response.json();
        this.error = null;
        alert(`Layout saved to library! (${data.count} total)`);
        
      } catch (error) {
        this.error = `Save failed: ${error.message}`;
        console.error('AI Layout save error:', error);
      }
    },
    
    getCurrentPostId() {
      // Try to get post ID from URL or other sources
      const urlParams = new URLSearchParams(window.location.search);
      return urlParams.get('post') || 0;
    }
  }
}
</script>

<style scoped>
.ai-layout-panel {
  padding: 20px;
  max-width: 800px;
  margin: 0 auto;
}

.ai-layout-header {
  text-align: center;
  margin-bottom: 30px;
}

.ai-layout-header h2 {
  margin: 0 0 10px 0;
  color: #333;
}

.ai-layout-header p {
  margin: 0;
  color: #666;
}

.ai-layout-section {
  margin-bottom: 30px;
  padding: 20px;
  background: #f9f9f9;
  border-radius: 8px;
}

.ai-layout-section h3 {
  margin: 0 0 15px 0;
  color: #333;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-control:focus {
  outline: none;
  border-color: #0073aa;
  box-shadow: 0 0 0 1px #0073aa;
}

.ai-layout-actions {
  margin-top: 20px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  text-decoration: none;
  display: inline-block;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: #0073aa;
  color: white;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-success {
  background: #28a745;
  color: white;
}

.ai-layout-result {
  margin-bottom: 20px;
}

.ai-layout-result h4 {
  margin: 0 0 10px 0;
  color: #333;
}

.ai-layout-code {
  background: #f4f4f4;
  padding: 15px;
  border-radius: 4px;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 12px;
  line-height: 1.4;
  overflow-x: auto;
  max-height: 300px;
  overflow-y: auto;
}

.ai-layout-error {
  background: #f8d7da;
  color: #721c24;
  padding: 15px;
  border-radius: 4px;
  margin-top: 20px;
}
</style>
