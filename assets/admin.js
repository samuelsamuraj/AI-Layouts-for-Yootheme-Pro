(function(){
  const el = document.getElementById('ai-layout-app');
  if(!el) return;

  el.innerHTML = `
  <div class="ai-layout">
    <div class="ai-grid">
      <div class="ai-card">
        <h2>Input</h2>
        <form id="ai-input-form">
          <label>URL <input id="ai-url" type="url" placeholder="https://example.com"/></label>
          <label>Rå tekst</label>
          <textarea id="ai-text" rows="6" placeholder="Indsæt kort beskrivelse eller HTML-uddrag" required></textarea>
          <label>Titel <input id="ai-title" type="text" placeholder="Layout titel"/></label>
          <label>Kommentar</label>
          <input id="ai-comment" type="text" placeholder="Designmål, tone, constraints"/>
          <div class="ai-actions">
            <button type="submit" id="ai-generate">Generér</button>
            <button type="button" id="ai-regenerate" disabled>Regenerér ulåste</button>
          </div>
        </form>
      </div>

      <div class="ai-card">
        <h2>Analyse</h2>
        <div id="ai-analysis-container">
          <pre id="ai-analysis" class="ai-pre"></pre>
        </div>
      </div>
      <div class="ai-card">
        <h2>Wireframe (DSL)</h2>
        <div id="ai-wireframe-container">
          <pre id="ai-wireframe" class="ai-pre"></pre>
        </div>
      </div>
      <div class="ai-card">
        <h2>YOOtheme JSON</h2>
        <div id="ai-layout-container">
          <pre id="ai-layout" class="ai-pre"></pre>
          <div class="ai-actions">
            <button id="ai-download" disabled>Download JSON</button>
            <button id="ai-apply" disabled>Apply to current page</button>
            <button id="ai-save-lib" disabled>Save to My Layouts (plugin)</button>
          </div>
        </div>
      </div>
    </div>
  </div>`;

  const $ = (id)=>document.getElementById(id);

  // Loading states
  function setLoading(loading) {
    const buttons = ['ai-generate', 'ai-regenerate', 'ai-download', 'ai-apply', 'ai-save-lib'];
    buttons.forEach(id => {
      const btn = $(id);
      if (btn) {
        btn.disabled = loading;
        btn.textContent = loading ? 'Loading...' : btn.textContent.replace('Loading...', '');
      }
    });
  }

  // Error handling
  function showError(message, containerId) {
    const container = $(containerId + '-container');
    if (container) {
      container.innerHTML = `<div class="ai-error">${message}</div>`;
    }
  }

  // Success handling
  function showSuccess(message, containerId) {
    const container = $(containerId + '-container');
    if (container) {
      container.innerHTML = `<div class="ai-success">${message}</div>`;
    }
  }

  async function call(path, body){
    try {
      const res = await fetch(`${AI_LAYOUT.restUrl}/${path}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AI_LAYOUT.nonce
        },
        body: JSON.stringify(body)
      });
      
      if(!res.ok){ 
        const errorText = await res.text();
        throw new Error(`HTTP ${res.status}: ${errorText}`);
      }
      
      return res.json();
    } catch (error) {
      console.error('API call failed:', error);
      throw error;
    }
  }

  function getInput(){
    const url = $('ai-url').value.trim();
    const text = $('ai-text').value.trim();
    
    if (!text && !url) {
      throw new Error('Please provide either URL or text input');
    }
    
    return {
      url: url || undefined,
      text: text || undefined
    };
  }

  // Form submission
  $('ai-input-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    try {
      const input = getInput();
      const title = $('ai-title').value.trim() || 'Generated Layout';
      const comment = $('ai-comment').value.trim();
      
      setLoading(true);
      
      // Clear previous results
      $('ai-analysis').textContent = 'Generating...';
      $('ai-wireframe').textContent = 'Generating...';
      $('ai-layout').textContent = 'Generating...';
      
      const data = await call('generate', { input, title, comment });
      
      $('ai-analysis').textContent = JSON.stringify(data.analysis, null, 2);
      $('ai-wireframe').textContent = JSON.stringify(data.wireframe, null, 2);
      $('ai-layout').textContent = JSON.stringify(data.layout, null, 2);
      
      window.__ai_wireframe = data.wireframe;
      window.__ai_layout_current = data.layout;
      
      // Enable buttons
      $('ai-regenerate').disabled = false;
      $('ai-download').disabled = false;
      $('ai-apply').disabled = false;
      $('ai-save-lib').disabled = false;
      
    } catch(e){
      showError('Fejl: ' + e.message, 'ai-analysis');
      $('ai-wireframe').textContent = 'Generation failed';
      $('ai-layout').textContent = 'Generation failed';
    } finally {
      setLoading(false);
    }
  });

  $('ai-regenerate').addEventListener('click', async () => {
    try{
      const wf = window.__ai_wireframe;
      if(!wf) {
        showError('Ingen wireframe endnu.', 'ai-wireframe');
        return;
      }
      
      setLoading(true);
      const data = await call('regenerate', { wireframe: wf });
      $('ai-wireframe').textContent = JSON.stringify(data.wireframe, null, 2);
      $('ai-layout').textContent = JSON.stringify(data.layout, null, 2);
      window.__ai_wireframe = data.wireframe;
      window.__ai_layout_current = data.layout;
    }catch(e){
      showError('Fejl: ' + e.message, 'ai-wireframe');
    } finally {
      setLoading(false);
    }
  });

  $('ai-download').addEventListener('click', async () => {
    if(!window.__ai_layout_current){ 
      showError('Intet layout genereret endnu.', 'ai-layout');
      return; 
    }
    try{
      setLoading(true);
      const data = await call('download', { layout: window.__ai_layout_current });
      window.open(data.url, '_blank');
    }catch(e){
      showError('Download-fejl: ' + e.message, 'ai-layout');
    } finally {
      setLoading(false);
    }
  });

  $('ai-apply').addEventListener('click', async () => {
    const postId = (new URLSearchParams(location.search)).get('post') || 0;
    if(!postId){ 
      showError('Kun tilgængelig på en redigér-side med ?post=ID', 'ai-layout');
      return; 
    }
    if(!window.__ai_layout_current){ 
      showError('Intet layout endnu.', 'ai-layout');
      return; 
    }
    try{
      setLoading(true);
      const data = await call('apply', { post_id: parseInt(postId,10), layout: window.__ai_layout_current });
      showSuccess('Anvendt til siden.', 'ai-layout');
    }catch(e){
      showError('Apply-fejl: ' + e.message, 'ai-layout');
    } finally {
      setLoading(false);
    }
  });

  $('ai-save-lib').addEventListener('click', async () => {
    if(!window.__ai_layout_current){ 
      showError('Intet layout endnu.', 'ai-layout');
      return; 
    }
    try{
      setLoading(true);
      const data = await call('save-to-library', { layout: window.__ai_layout_current });
      showSuccess('Gemt i plugin-bibliotek. ('+data.count+' i alt)', 'ai-layout');
    }catch(e){
      showError('Fejl: ' + e.message, 'ai-layout');
    } finally {
      setLoading(false);
    }
  });
})();
