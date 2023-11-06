<template>
  <teleport to="body">
    <div ref="modal" class="modal fade" :id="idName"
      v-bind="$attrs"
      tabindex="-1"
      role="dialog"
      :aria-labelledby="idName + '-label'"
      :aria-hidden="visible ? 'true' : 'false'"
      :data-bs-backdrop="backdrop"
    >
      <div class="modal-dialog"
        role="document"
        :class="size ? 'modal-' + size : null">
        <div class="modal-content">
          <template v-if="visible">
            <template v-if="hasSlots('header-element')">
              <slot name="header-element"></slot>
            </template>
            <div v-else class="modal-header">
              <slot name="header">
                <div class="modal-title" :id="idName + '-label'">
                    <h4>{{ title }}</h4>
                </div>
              </slot>
              <button type="button" class="close btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true" class="visually-hidden">&times;</span>
              </button>
            </div>
          </template>
          <div v-if="visible" class="modal-body">
            <slot></slot>
          </div>
          <div v-if="visible && hasSlots('footer')" class="modal-footer">
            <slot name="footer"></slot>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script>
import Modal from 'bootstrap/js/src/modal';
import { onMounted, reactive, ref, toRefs, watch } from 'vue';

export default {
  name: 'BsModal',
  inheritAttrs: false,
  props: {
    id: String,
    open: {
      type: Boolean,
      default: false
    },
    size: String,
    title: String,
    backdrop: {
      type: [ String, Boolean ],
      default: true
    }
  },
  emits: [
    'show',
    'shown',
    'hide',
    'hidden'
  ],
  setup(props, { emit, slots }) {
    const modal = ref(null);
    const state = reactive({
      idName: props.id || 'modal-' + (Math.random() + 1).toString(36).substring(7),
      visible: props.open,
    });

    watch(() => state.visible, (v, oldV) => {
      if (!oldV && v) {
        getModalInstance().show();
      }
      if (oldV && !v) {
        getModalInstance().hide();
      }
    });

    watch(() => props.open, (v) => {
      state.visible = v
    });

    watch(() => props.id, (idName) => {
      state.idName = idName;
    });

    onMounted(() => {
      modal.value.addEventListener('show.bs.modal', (e) => {
        emit('show', e);
      });

      modal.value.addEventListener('shown.bs.modal', (e) => {
        emit('shown', e);
      });
      modal.value.addEventListener('hide.bs.modal', (e) => {
        emit('hide', e);
      });

      modal.value.addEventListener('hidden.bs.modal', (e) => {
        emit('hidden', e);
      });
    });

    function getModalInstance() {
      return Modal.getOrCreateInstance(modal.value);
    }

    function hasSlots(name) {
      return slots[name] !== undefined;
    }

    return {
      ...toRefs(state),
      modal,
      hasSlots
    };
  }
};
</script>

<style scoped>

</style>
