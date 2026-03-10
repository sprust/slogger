package main

import (
	"fmt"
	"os"
	"os/exec"
	"time"
)

func clearScreen() {
	var cmd *exec.Cmd

	cmd = exec.Command("clear")

	cmd.Stdout = os.Stdout

	err := cmd.Run()

	if err != nil {
		panic(err)
	}
}

func readStats(filepath string) (string, error) {
	data, err := os.ReadFile(filepath)

	if err != nil {
		return "", err
	}

	return string(data), nil
}

func prettyPrint(stats string) {
	fmt.Println("=== Stats Monitor ===")
	fmt.Println()

	fmt.Println(stats)
	fmt.Println()
	fmt.Printf("Last refresh: %s\n", time.Now().Format("2006-01-02 15:04:05"))
}

func main() {
	filepath := "storage/stats.json"

	ticker := time.NewTicker(1 * time.Second)
	defer ticker.Stop()

	// Initial read and display
	stats, err := readStats(filepath)

	if err != nil {
		fmt.Printf("Error reading stats: %v\n", err)
	} else {
		clearScreen()
		prettyPrint(stats)
	}

	// Refresh every second
	for range ticker.C {
		stats, err := readStats(filepath)

		if err != nil {
			clearScreen()

			fmt.Printf("Error reading stats: %v\n", err)

			continue
		}

		clearScreen()
		prettyPrint(stats)
	}
}
