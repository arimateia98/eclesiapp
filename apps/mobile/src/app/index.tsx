import { SafeAreaView, StyleSheet, Text, View } from 'react-native';

export default function HomeScreen() {
  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.container}>
        <Text style={styles.eyebrow}>GESTÃO PASTORAL</Text>
        <Text accessibilityRole="header" style={styles.title}>
          eclEZapp
        </Text>
        <Text style={styles.description}>
          Sua agenda pastoral e suas escalas em um só lugar.
        </Text>
        <View accessibilityRole="text" style={styles.status}>
          <View style={styles.dot} />
          <Text style={styles.statusText}>Fundação em desenvolvimento</Text>
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#f8fbf9',
  },
  container: {
    flex: 1,
    justifyContent: 'center',
    paddingHorizontal: 28,
  },
  eyebrow: {
    marginBottom: 10,
    color: '#27644f',
    fontSize: 13,
    fontWeight: '700',
    letterSpacing: 1.5,
  },
  title: {
    color: '#163c2f',
    fontSize: 64,
    fontWeight: '800',
    letterSpacing: -4,
  },
  description: {
    maxWidth: 340,
    marginTop: 18,
    color: '#4f5f59',
    fontSize: 20,
    lineHeight: 30,
  },
  status: {
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginTop: 34,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: '#cadbd4',
    borderRadius: 999,
    backgroundColor: '#f3faf7',
  },
  dot: {
    width: 9,
    height: 9,
    borderRadius: 5,
    backgroundColor: '#318663',
  },
  statusText: {
    color: '#25483c',
    fontSize: 14,
    fontWeight: '600',
  },
});
