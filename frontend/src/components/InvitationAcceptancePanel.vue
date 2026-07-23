<script setup lang="ts">
import { reactive, ref } from 'vue'
import { acceptAccountInvitation, ApiError } from '../services/api'
import type { AuthSession } from '../types/api'

const props = defineProps<{
  token: string
}>()

const emit = defineEmits<{
  authenticated: [session: AuthSession]
  cancel: []
}>()

const busy = ref(false)
const errorMessage = ref<string | null>(null)
const fields = reactive({
  name: '',
  password: '',
  passwordConfirmation: '',
})

async function submit(): Promise<void> {
  busy.value = true
  errorMessage.value = null

  try {
    const session = await acceptAccountInvitation({
      token: props.token,
      name: fields.name,
      password: fields.password,
      password_confirmation: fields.passwordConfirmation,
      device_name: 'painel-web',
    })

    emit('authenticated', session)
  } catch (error) {
    if (error instanceof ApiError) {
      errorMessage.value = Object.values(error.validationErrors).flat()[0] || error.message
    } else {
      errorMessage.value = 'Não foi possível validar este convite.'
    }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section
    class="invitation-card"
    aria-labelledby="invitation-title"
  >
    <span
      class="invitation-card__mark"
      aria-hidden="true"
    >✦</span>
    <span class="eyebrow">Convite pessoal</span>
    <h1 id="invitation-title">
      Seu lugar já está preparado.
    </h1>
    <p>
      Crie uma senha para assumir o perfil cadastrado pela sua comunidade. O convite será encerrado
      assim que você entrar.
    </p>

    <form
      class="form-stack invitation-form"
      data-test="invitation-form"
      @submit.prevent="submit"
    >
      <label class="field">
        <span>Como quer ser chamado</span>
        <input
          v-model.trim="fields.name"
          name="name"
          autocomplete="name"
          required
          placeholder="Ex.: João"
        >
      </label>
      <div class="field-row">
        <label class="field">
          <span>Crie uma senha</span>
          <input
            v-model="fields.password"
            name="password"
            type="password"
            autocomplete="new-password"
            minlength="8"
            required
            placeholder="Mínimo de 8 caracteres"
          >
        </label>
        <label class="field">
          <span>Confirme a senha</span>
          <input
            v-model="fields.passwordConfirmation"
            name="passwordConfirmation"
            type="password"
            autocomplete="new-password"
            minlength="8"
            required
            placeholder="Repita a senha"
          >
        </label>
      </div>

      <p
        v-if="errorMessage"
        class="form-error"
        data-test="invitation-error"
        role="alert"
      >
        {{ errorMessage }}
      </p>

      <button
        class="primary-button"
        type="submit"
        :disabled="busy"
      >
        <span>{{ busy ? 'Validando…' : 'Criar acesso e entrar' }}</span>
        <span aria-hidden="true">→</span>
      </button>
      <button
        class="text-button"
        type="button"
        @click="emit('cancel')"
      >
        Voltar para o início
      </button>
    </form>
  </section>
</template>
