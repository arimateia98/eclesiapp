<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { fetchHealth } from './services/api'
import type { HealthData } from './types/api'

const health = ref<HealthData | null>(null)
const error = ref<string | null>(null)
const controller = new AbortController()

onMounted(async () => {
  try {
    health.value = await fetchHealth(controller.signal)
  } catch {
    error.value = 'A API ainda não está disponível.'
  }
})

onBeforeUnmount(() => controller.abort())
</script>

<template>
  <main class="shell">
    <section
      class="card"
      aria-labelledby="page-title"
    >
      <span class="eyebrow">Fundação do projeto</span>
      <h1 id="page-title">
        Eclesiapp
      </h1>
      <p class="lead">
        Gestão pastoral com escalas seguras, claras e sem conflitos.
      </p>

      <div
        v-if="health"
        class="status status--online"
        data-test="api-status"
        role="status"
      >
        <span
          class="status__dot"
          aria-hidden="true"
        />
        API conectada
      </div>
      <div
        v-else-if="error"
        class="status status--offline"
        data-test="api-status"
        role="alert"
      >
        <span
          class="status__dot"
          aria-hidden="true"
        />
        {{ error }}
      </div>
      <div
        v-else
        class="status"
        data-test="api-status"
        role="status"
      >
        <span
          class="status__dot"
          aria-hidden="true"
        />
        Verificando a API…
      </div>
    </section>
  </main>
</template>
